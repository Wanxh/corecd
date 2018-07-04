#!/bin/sh

#整合配置文件
echo "mysql.default.type = \"mysql\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.host = \"${MYSQL_HOST}\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.port = ${MYSQL_PORT}" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.database = \"${MYSQL_DATABASE}\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.username = \"${MYSQL_USERNAME}\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.password = \"${MYSQL_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.prefix = \"${MYSQL_PREFIX}\"" >> ${APP_PATH}/conf/application.ini && \
echo "mysql.default.charset = \"utf8\"" >> ${APP_PATH}/conf/application.ini && \
echo "redis.default.host = \"${REDIS_HOST}\""  >> ${APP_PATH}/conf/application.ini && \
echo "redis.default.port = ${REDIS_PORT}" >> ${APP_PATH}/conf/application.ini && \
echo "redis.default.database = 0" >> ${APP_PATH}/conf/application.ini && \
echo "redis.default.password = \"${REDIS_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
echo "redis.default.timeout = 2" >> ${APP_PATH}/conf/application.ini && \
echo "redis.session.host = \"${REDIS_HOST}\""  >> ${APP_PATH}/conf/application.ini && \
echo "redis.session.port = ${REDIS_PORT}" >> ${APP_PATH}/conf/application.ini && \
echo "redis.session.database = 0" >> ${APP_PATH}/conf/application.ini && \
echo "redis.session.password = \"${REDIS_PASSWORD}\"" >> ${APP_PATH}/conf/application.ini && \
echo "redis.session.timeout = 2" >> ${APP_PATH}/conf/application.ini

#初始化监听脚本
php ${APP_PATH}/bin/cli console/listen/index >> ${LOG_FILE} &