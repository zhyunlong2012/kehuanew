FROM skiychan/nginx-php7:4.10.0
MAINTAINER Madfrog <zhyunlong@163.com>
# NGINX
ADD nginx.conf /usr/local/nginx/conf/
ADD vhost /usr/local/nginx/conf/vhost

ADD www ${NGX_WWW_ROOT}