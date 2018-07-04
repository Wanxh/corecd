#基于alpine3.7+nginx+php7.2.4-fpm
FROM lin2798003/anp:latest

ENV APP_PATH /var/www/html
ENV APP_PATH_INDEX /var/www/html/public

#mysql config
ENV MYSQL_HOST 127.0.0.1
ENV MYSQL_PORT 3306
ENV MYSQL_USERNAME root
ENV MYSQL_PASSWORD root
ENV MYSQL_DATABASE corecd
ENV MYSQL_PREFIX c_

#redis config
ENV REDIS_HOST 127.0.0.1
ENV REDIS_PORT 6379
ENV REDIS_DATABASE 0
ENV REDIS_PASSWORD ""

#init shell
ENV APP_INIT_SHELL "/bin/sh ${APP_PATH}/init.sh"
#tailf log file
ENV LOG_FILE /cli.log

#将所有代码复制到镜像的/var/www/html
COPY . ${APP_PATH}

#安装composer
#将初始化脚本加入到/extra/external.sh中
#/extra/external.sh会在CMD中自动执行
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    composer install