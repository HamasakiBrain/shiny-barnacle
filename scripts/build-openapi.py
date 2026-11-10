"""Rebuild the checked-in OpenAPI document. Python is needed only to edit docs."""
import json
from pathlib import Path
def ref(n): return {'$ref':'#/components/schemas/'+n}
def string(example, **kw): return {'type':'string','example':example,**kw}
def integer(example=1, **kw): return {'type':'integer','example':example,**kw}
def obj(props, required=None): return {'type':'object','properties':props,'required':required or list(props)}
def array(items): return {'type':'array','items':items}
def enum(values): return string(values[0],enum=values)
schemas={}
schemas['Address']=obj({'name':string('Demo Customer',maxLength=100),'line1':string('1 Main Street',maxLength=255),'city':string('Boston',maxLength=100),'postal_code':string('02101',maxLength=20),'country':string('US',pattern='^[A-Z]{2}$')})
schemas['User']=obj({'id':integer(),'name':string('Demo Customer'),'email':string('customer@example.test',format='email'),'role':enum(['customer','admin'])})
schemas['Category']=obj({'id':integer(),'name':string('Office'),'slug':string('office'),'is_active':{'type':'boolean','example':True}})
schemas['Product']=obj({'id':integer(),'name':string('Desk Lamp'),'slug':string('desk-lamp'),'sku':string('LAMP-001'),'description':string('Adjustable desk lamp.'),'price':integer(2500,minimum=1),'currency':enum(['USD']),'stock':integer(50,minimum=0),'is_active':{'type':'boolean','example':True},'category':ref('Category'),'average_rating':{'type':'number','nullable':True,'example':4.5}})
schemas['CartItem']=obj({'id':integer(),'quantity':integer(2),'product':ref('Product'),'subtotal':integer(5000)})
schemas['OrderItem']=obj({'product_id':integer(),'name':string('Desk Lamp'),'sku':string('LAMP-001'),'unit_price':integer(2500),'quantity':integer(2),'subtotal':integer(5000)})
schemas['Payment']=obj({'method':enum(['cod','mock']),'status':enum(['pending','paid','failed','refunded','cancelled']),'amount':integer(5000),'paid_at':{'type':'string','format':'date-time','nullable':True,'example':None}})
schemas['Order']=obj({'id':integer(),'status':enum(['pending','processing','shipped','delivered','cancelled']),'total':integer(5000),'currency':enum(['USD']),'shipping_address':ref('Address'),'items':array(ref('OrderItem')),'payment':ref('Payment'),'created_at':string('2026-11-10T12:00:00+00:00',format='date-time')})
schemas['Review']=obj({'id':integer(),'product_id':integer(),'rating':integer(5,minimum=1,maximum=5),'body':string('Works well.'),'author':obj({'id':integer(),'name':string('Demo Customer')}),'created_at':string('2026-11-10T12:00:00+00:00',format='date-time')})
schemas['Favorite']=obj({'id':integer(),'product':ref('Product')})
schemas['Notification']=obj({'id':string('d6995800-82d8-5801-94e6-996f341c9e57',format='uuid'),'data':obj({'order_id':integer(),'total':integer(5000),'currency':enum(['USD']),'message':string('Your order has been placed.')}),'read_at':{'type':'string','nullable':True,'example':None},'created_at':string('2026-11-10T12:00:00+00:00',format='date-time')})
schemas['Auth']=obj({'user':ref('User'),'token':string('1|example-token-replace-with-login-response'),'token_type':enum(['Bearer'])})
schemas['Error']=obj({'message':string('This action is unauthorized.')})
schemas['ValidationError']=obj({'message':string('The given data was invalid.'),'errors':{'type':'object','additionalProperties':array({'type':'string'}),'example':{'email':['The email field is required.']}}})
email=string('customer@example.test',format='email',maxLength=255)
password=string('ReplaceWithYourLocalDemoPassword',minLength=12,maxLength=128)
schemas['RegisterInput']=obj({'name':string('Demo Customer',maxLength=100),'email':email,'password':password,'password_confirmation':password})
schemas['LoginInput']=obj({'email':email,'password':string('ReplaceWithYourLocalDemoPassword',maxLength=128)})
schemas['CategoryInput']=obj({'name':string('Office',maxLength=255),'slug':string('office',maxLength=255,description='Letters, numbers, dashes and underscores; must be unique.'),'is_active':{'type':'boolean','example':True}},['name','slug'])
schemas['ProductInput']=obj({'category_id':integer(),'name':string('Desk Lamp',maxLength=255),'slug':string('desk-lamp',maxLength=255,description='Letters, numbers, dashes and underscores; must be unique.'),'sku':string('LAMP-001',maxLength=255),'description':string('Adjustable desk lamp.',maxLength=10000),'price':integer(2500,minimum=1,maximum=100000000),'stock':integer(50,minimum=0,maximum=1000000),'is_active':{'type':'boolean','example':True}},['category_id','name','slug','sku','description','price','stock'])
for name in ['Category','Product']:
 schemas[name+'Update']=dict(schemas[name+'Input'])
 schemas[name+'Update'].pop('required')
schemas['CartInput']=obj({'product_id':integer(),'quantity':integer(2,minimum=1,maximum=100)})
schemas['CheckoutInput']=obj({'idempotency_key':string('606386a3-16e2-4124-8e20-d34f377085cc',format='uuid'),'payment_method':enum(['cod','mock']),'shipping_address':ref('Address')})
schemas['StatusInput']=obj({'status':enum(['pending','processing','shipped','delivered','cancelled'])})
schemas['MockInput']=obj({'outcome':enum(['success','failure'])})
schemas['ReviewInput']=obj({'rating':integer(5,minimum=1,maximum=5),'body':string('Works well.',maxLength=2000)})
def example(schema):
 if '$ref' in schema: return example(schemas[schema['$ref'].split('/')[-1]])
 if 'example' in schema: return schema['example']
 if schema.get('type')=='object': return {k:example(v) for k,v in schema.get('properties',{}).items()}
 if schema.get('type')=='array': return [example(schema['items'])]
 return None
paths={}
def endpoint(method,path,summary,tag,output=None,body=None,auth=False,admin=False,status=200,paginated=False,many=False,description=''):
 parameters=[]
 for part in path.split('/'):
  if part.startswith('{'): parameters.append({'name':part[1:-1],'in':'path','required':True,'schema':integer(minimum=1),'description':'Numeric resource ID.'})
 if paginated: parameters.append({'name':'page','in':'query','schema':integer(minimum=1),'description':'Page number; default 1.'})
 operation={'operationId':method+'_'+path.strip('/').replace('/','_').replace('{','').replace('}','').replace('-','_'),'tags':[tag],'summary':summary,'description':('Admin role required. ' if admin else '')+description,'security':[{'bearerAuth':[]}] if auth or admin else [],'parameters':parameters,'responses':{}}
 if body: operation['requestBody']={'required':True,'content':{'application/json':{'schema':ref(body),'example':example(ref(body))}}}
 response={'description':'No content.' if status==204 else 'Successful response.'}
 if output:
  data=array(ref(output)) if many or paginated else ref(output)
  result=obj({'data':data})
  if paginated:
   result['properties']['links']=obj({'first':string('http://localhost:8080/api/v1'+path+'?page=1'),'last':string('http://localhost:8080/api/v1'+path+'?page=1'),'prev':{'type':'string','nullable':True,'example':None},'next':{'type':'string','nullable':True,'example':None}})
   result['properties']['meta']=obj({'current_page':integer(),'last_page':integer(),'per_page':integer(20),'total':integer()})
  response['content']={'application/json':{'schema':result,'example':example(result)}}
 operation['responses'][str(status)]=response
 for code,text in [(401,'Missing, expired or revoked bearer token.'),(403,'Permission denied.'),(404,'Resource not found or inactive.'),(409,'Business conflict: stock, state, purchase requirement or idempotency mismatch.'),(422,'Invalid request parameters or body.'),(429,'Rate limit exceeded.'),(500,'Unexpected server error.')]:
  if code in [401,403] and not(auth or admin): continue
  err='ValidationError' if code==422 else 'Error'
  operation['responses'][str(code)]={'description':text,'content':{'application/json':{'schema':ref(err),'example':example(ref(err)) if code==422 else {'message':text}}}}
 paths.setdefault(path,{})[method]=operation

endpoint('post','/auth/register','Register a Customer','Authentication','Auth','RegisterInput',status=201,description='Role cannot be supplied. Tokens expire after 24 hours.')
endpoint('post','/auth/login','Create a bearer token','Authentication','Auth','LoginInput')
endpoint('get','/auth/me','Current account','Authentication','User',auth=True)
endpoint('post','/auth/logout','Revoke current token','Authentication',auth=True,status=204)
endpoint('get','/categories','List active categories','Catalog','Category',many=True)
endpoint('get','/products','Search active products','Catalog','Product',paginated=True)
for name,schema in {'search':string('lamp',maxLength=100),'category_id':integer(),'min_price':integer(0,minimum=0),'max_price':integer(100000,minimum=0),'sort':enum(['id','price','name']),'direction':enum(['asc','desc']),'per_page':integer(20,minimum=1,maximum=100)}.items():
 paths['/products']['get']['parameters'].append({'name':name,'in':'query','schema':schema})
endpoint('get','/products/{product}','Product details','Catalog','Product')
endpoint('get','/cart','View your cart','Cart','CartItem',auth=True,many=True)
endpoint('put','/cart/items','Set absolute product quantity','Cart','CartItem','CartInput',auth=True,many=True,description='Replaces quantity, does not increment. Cart prices are current, stock is reserved only at checkout.')
endpoint('delete','/cart/items/{product}','Remove your cart item','Cart',auth=True,status=204)
endpoint('delete','/cart','Clear your cart','Cart',auth=True,status=204)
endpoint('get','/orders','List your orders','Orders','Order',auth=True,paginated=True)
endpoint('post','/orders','Checkout cart','Orders','Order','CheckoutInput',auth=True,status=201,description='Locks stock and snapshots prices. Same idempotency key and body returns the existing order (201), even after the cart is cleared. Different body with same key: 409. Prices, totals and user IDs are server-controlled.')
endpoint('get','/orders/{order}','Read an order','Orders','Order',auth=True,description='Owner or Admin only.')
endpoint('post','/orders/{order}/cancel','Cancel an order','Orders','Order',auth=True,description='Customer: pending only. Admin: pending or processing. Restores stock once. Repeated cancellation succeeds. Paid mock payment becomes refunded.')
endpoint('post','/orders/{order}/payments/mock','Simulate payment result','Payments','Order','MockInput',auth=True,description='Owner only. Mock method only. Failure leaves a pending order retryable; success is idempotent. No real money is processed.')
endpoint('get','/products/{product}/reviews','Public product reviews','Reviews','Review',paginated=True)
endpoint('put','/products/{product}/review','Create or replace your review','Reviews','Review','ReviewInput',auth=True,description='Requires a delivered purchase. One review per product/customer.')
endpoint('delete','/reviews/{review}','Delete review','Reviews',auth=True,status=204,description='Author or Admin only.')
endpoint('get','/favorites','List your favorites','Favorites','Favorite',auth=True,paginated=True)
endpoint('put','/favorites/{product}','Save favorite idempotently','Favorites',auth=True,status=204)
endpoint('delete','/favorites/{product}','Remove favorite idempotently','Favorites',auth=True,status=204)
endpoint('get','/notifications','List your database notifications','Notifications','Notification',auth=True,paginated=True,description='Order confirmation arrives asynchronously through the Redis worker.')
for plural,singular,resource in [('products','product','Product'),('categories','category','Category')]:
 endpoint('post','/admin/'+plural,'Create '+singular,'Administration',resource,resource+'Input',admin=True,status=201)
 endpoint('patch','/admin/'+plural+'/{'+singular+'}','Update '+singular,'Administration',resource,resource+'Update',admin=True)
 endpoint('delete','/admin/'+plural+'/{'+singular+'}','Deactivate '+singular,'Administration',admin=True,status=204,description='Soft deactivation preserves order history. PATCH is_active=true to reactivate.')
endpoint('get','/admin/orders','List all orders','Administration','Order',admin=True,paginated=True)
endpoint('patch','/admin/orders/{order}/status','Transition order state','Administration','Order','StatusInput',admin=True,description='pending -> processing -> shipped -> delivered; pending/processing -> cancelled. Mock must be paid before processing. COD settles on delivery. Repeating the current status is idempotent.')
spec={'openapi':'3.0.3','info':{'title':'Market API','version':'1.0.0','description':'Portfolio e-commerce backend. Money is integer USD cents. Send Accept: application/json and Content-Type: application/json. External payments are not supported.'},'servers':[{'url':'/api/v1','description':'Current host'}],'tags':[{'name':x} for x in ['Authentication','Catalog','Cart','Orders','Payments','Reviews','Favorites','Notifications','Administration']],'paths':paths,'components':{'securitySchemes':{'bearerAuth':{'type':'http','scheme':'bearer','description':'Laravel Sanctum token from register/login. Expires after 24 hours.'}},'schemas':schemas}}
Path('public/openapi.json').write_text(json.dumps(spec,indent=2)+'\n')
print(f"Documented {sum(len(v) for v in paths.values())} operations.")
