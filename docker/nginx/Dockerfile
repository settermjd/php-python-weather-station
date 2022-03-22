FROM nginx:alpine as dependencies

WORKDIR /var/www/html

COPY ../production/nginx .

RUN apk add --update nodejs npm \
    && npm install

RUN NODE_ENV=production npx tailwindcss \
  -i src/css/styles.css \
  -o public/css/styles.css \
  --minify

FROM nginx:alpine as deployed

WORKDIR /var/www/html

COPY ./docker/nginx/default.prod.conf /etc/nginx/conf.d/default.conf

COPY ../production/nginx .
