#基于alpine3.7+nginx+php7.2.4-fpm
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
ENV REDIS_PASSWORD ""

#tailf log file
ENV LOG_FILE /cli.log

#将所有代码复制到镜像的/var/www/html
COPY . ${APP_PATH}

#整合配置文件
RUN echo "mysql.default.type = \"mysql\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.host = \"${MYSQL_HOST}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.port = ${MYSQL_PORT}" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.database = \"${MYSQL_DATABASE}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.username = \"${MYSQL_USERNAME}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.password = \"${MYSQL_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.prefix = \"${MYSQL_PREFIX}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "mysql.default.charset = \"utf8\"" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.default.host = \"${REDIS_HOST}\""  >> ${APP_PATH}/conf/application.ini && \
    echo "redis.default.port = ${MYSQL_PORT}" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.default.database = 0" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.default.password = \"${REDIS_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.default.timeout = 2" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.session.host = \"${REDIS_HOST}\""  >> ${APP_PATH}/conf/application.ini && \
    echo "redis.session.port = ${MYSQL_PORT}" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.session.database = 0" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.session.password = \"${REDIS_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
    echo "redis.session.timeout = 2" >> ${APP_PATH}/conf/application.ini

#安装composer
#将初始化脚本加入到/extra/external.sh中
#/extra/external.sh会在CMD中自动执行
RUN echo "setup composer..." >> ${LOG_FILE} && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    echo "composer install..." >> ${LOG_FILE} && \
    composer install && \
    echo "php ${APP_PATH}/bin/cli console/listen/index >> ${LOG_FILE} &" >> /extra/external.sh && \
    echo "init success" >> ${LOG_FILE}