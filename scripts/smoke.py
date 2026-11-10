'''Exercise the running Nginx/PHP/MySQL/Redis stack without printing credentials.'''
import json, os, time, uuid, urllib.request, urllib.error
from pathlib import Path

env={}
for line in Path('.env').read_text().splitlines():
    if line and not line.startswith('#') and '=' in line:
        key,value=line.split('=',1)
        env[key]=value.strip('"')
base=os.environ.get('SMOKE_URL','http://127.0.0.1:'+env.get('APP_PORT','8080'))
def call(method,path,payload=None,token=None,status=200):
    headers={'Accept':'application/json','Content-Type':'application/json'}
    if token: headers['Authorization']='Bearer '+token
    request=urllib.request.Request(base+'/api/v1'+path,data=json.dumps(payload).encode() if payload is not None else None,headers=headers,method=method)
    try:
        with urllib.request.urlopen(request,timeout=15) as response:
            code=response.status; data=response.read()
    except urllib.error.HTTPError as error:
        code=error.code; data=error.read()
    assert code==status, f'{method} {path}: expected {status}, got {code}: {data[:300]!r}'
    return json.loads(data) if data else None
customer=call('POST','/auth/login',{'email':env['DEMO_CUSTOMER_EMAIL'],'password':env['DEMO_PASSWORD']})['data']['token']
admin=call('POST','/auth/login',{'email':env['DEMO_ADMIN_EMAIL'],'password':env['DEMO_PASSWORD']})['data']['token']
product=call('GET','/products')['data'][0]
pid=product['id']
address={'name':'Smoke Customer','line1':'1 Main St','city':'Boston','postal_code':'02101','country':'US'}
call('DELETE','/cart',token=customer,status=204)
call('PUT','/favorites/'+str(pid),token=customer,status=204)
assert any(x['product']['id']==pid for x in call('GET','/favorites',token=customer)['data'])
call('DELETE','/favorites/'+str(pid),token=customer,status=204)
order_ids=[]
for method in ['cod','mock']:
    call('PUT','/cart/items',{'product_id':pid,'quantity':1},customer)
    payload={'idempotency_key':str(uuid.uuid4()),'payment_method':method,'shipping_address':address}
    order=call('POST','/orders',payload,customer,status=201)['data']
    oid=order['id']; order_ids.append(oid)
    assert order['total']==product['price']
    assert call('POST','/orders',payload,customer,status=201)['data']['id']==oid
    if method=='mock':
        assert call('POST',f'/orders/{oid}/payments/mock',{'outcome':'failure'},customer)['data']['payment']['status']=='failed'
        call('PATCH',f'/admin/orders/{oid}/status',{'status':'processing'},admin,status=409)
        assert call('POST',f'/orders/{oid}/payments/mock',{'outcome':'success'},customer)['data']['payment']['status']=='paid'
    for state in ['processing','shipped','delivered']:
        call('PATCH',f'/admin/orders/{oid}/status',{'status':state},admin)
    assert call('GET',f'/orders/{oid}',token=customer)['data']['payment']['status']=='paid'
call('PUT',f'/products/{pid}/review',{'rating':5,'body':'Verified Docker smoke purchase.'},customer)
for attempt in range(40):
    notifications=call('GET','/notifications',token=customer)['data']
    notified={n['data']['order_id'] for n in notifications}
    if set(order_ids)<=notified: break
    time.sleep(0.5)
assert set(order_ids)<=notified, 'Redis worker did not deliver order notifications.'
assert call('GET',f'/products/{pid}')['data']['stock']==product['stock']-2
for token in [customer,admin]:
    call('POST','/auth/logout',token=token,status=204)
    call('GET','/auth/me',token=token,status=401)
for path in ['/up','/docs','/openapi.json','/vendor/swagger-ui/swagger-ui-bundle.js']:
    with urllib.request.urlopen(base+path,timeout=15) as response:
        assert response.status==200
print('PASS: catalog, favorites, COD delivery, mock failure/retry, checkout idempotency, review, Redis notifications, token revocation and Swagger HTTP assets.')
