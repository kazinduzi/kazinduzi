# Use Alpine Linux
FROM nginx:alpine

# Declare maintainer
MAINTAINER Emmanuel Ndayiragije

# modified version from php:alpine image
# ensure www-data user exists
RUN set -x ; \
  addgroup -g 82 -S www-data ; \
  adduser -u 82 -D -S -G www-data www-data
# 82 is the standard uid/gid for "www-data" in Alpine


# Timezone
ENV TIMEZONE Europe/Amsterdam

# Let's roll
RUN	apk update && \
	apk upgrade && \
	apk add --update openssl && \
	apk add --update supervisor tzdata && \
	
	cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
	echo "${TIMEZONE}" > /etc/timezone && \
	mkdir /etc/nginx/certificates && \
	openssl req \
		-x509 \
		-newkey rsa:2048 \
		-keyout /etc/nginx/certificates/key.pem \
		-out /etc/nginx/certificates/cert.pem \
		-days 365 \
		-nodes \
		-subj /CN=localhost && \
    rm -Rf /var/www/* && \
	mkdir -p /var/www/html && \
	apk del tzdata && \
	rm -rf /var/cache/apk/*

ADD etc/supervisord.conf /etc/supervisord.conf
ADD etc/nginx.conf /etc/nginx/nginx.conf
ADD etc/common.conf /etc/nginx/common.conf
ADD etc/conf.d/default.conf /etc/nginx/conf.d/default.conf
ADD etc/conf.d/ssl.conf /etc/nginx/conf.d/ssl.conf
ADD etc/conf.d/kazinduzi.conf /etc/nginx/conf.d/kazinduzi.conf

RUN echo "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf

# Expose volumes
VOLUME ["/etc/nginx/conf.d", "/var/log/nginx", "/var/www/html"]

# Expose ports
EXPOSE 80 443

# Entry point
ENTRYPOINT ["/usr/sbin/nginx", "-g", "daemon off;"]