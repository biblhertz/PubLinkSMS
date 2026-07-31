FROM mysql:8.0

COPY ./mysql/schema.sql /docker-entrypoint-initdb.d/schema.sql
