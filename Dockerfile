#基于我们自营的非常牛逼的alpine3.7+nginx+php7.2.4-fpm
FROM lin2798003/anp:stable

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
ENV REDIS_PASSWORD

#将所有代码复制到镜像的/var/www/html
COPY . ${APP_PATH}

#整合配置文件
RUN echo "
;mysql单主配置
mysql.default.type = "mysql"
mysql.default.host = "${MYSQL_HOST}"
mysql.default.port = ${MYSQL_PORT}
mysql.default.database = "${MYSQL_DATABASE}"
mysql.default.username = "${MYSQL_USERNAME}"
mysql.default.password = "${MYSQL_PASSWORD}"
mysql.default.prefix = "${MYSQL_PREFIX}"
mysql.default.charset = "utf8"
;redis config
redis.default.host = "${REDIS_HOST}"
redis.default.port = ${MYSQL_PORT}
redis.default.database = 0
redis.default.password = "${REDIS_PASSWORD}"
redis.default.timeout = 2
;redis session
redis.session.host = "${REDIS_HOST}"
redis.session.port = ${MYSQL_PORT}
redis.session.database = 0
redis.session.password = "${REDIS_PASSWORD}"
redis.session.timeout = 2
    " >> ${APP_PATH}/conf/application.ini

#安装composer
#将初始化脚本加入到/extra/external.sh中
#/extra/external.sh会在CMD中自动执行
RUN echo "setup composer..." >> /cli.log && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    echo "composer install..." >> /cli.log && \
    composer install && \
    echo "php ${APP_PATH}/bin/cli console/listen/index >> /cli.log &" >> /extra/external.sh &&
    echo "init success" >> /cli.log