ARG APP_IMAGE=hookline-prod
FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

RUN apk add --no-cache curl

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
