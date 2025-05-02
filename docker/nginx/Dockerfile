FROM nginx:latest
RUN apt-get update && apt-get install apt-file -y && apt-file update && apt-get install vim -y
COPY ./nginx/default.conf /etc/nginx/conf.d/

