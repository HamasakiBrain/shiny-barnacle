<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market API documentation</title>
    <link rel="stylesheet" href="/vendor/swagger-ui/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="/vendor/swagger-ui/swagger-ui-bundle.js"></script>
<script>
window.onload = function () {
    SwaggerUIBundle({
        url: '/openapi.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        persistAuthorization: false,
        validatorUrl: null
    });
};
</script>
</body>
</html>
