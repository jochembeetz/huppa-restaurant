# What would I do differently if I had more time and/or it would go in production

- Create DTO's for all API responses (potentially use spatie data library)
- Implement OAuth2 flow instead of api key auth
- Place both applications in one secure network and/or implement IP whitelisting
- Containerize the application (including postgres, redis) using sail
- Add Laravel Horizon for easier queue management (includes logs for debugging)
- Add appropriate retry mechanism for webhook failures
- Webhooks based on order status trails/logs instead of the order's status
- Notifications/alerts to developers on unexpected errors

# How should the website implement this feature?


- curl -H "X-API-Key: secret-key" http://localhost:8000/api/orders/1