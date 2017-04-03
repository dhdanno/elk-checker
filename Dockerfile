FROM php:7.0-cli

ADD . /usr/src/myapp

WORKDIR /usr/src/myapp

CMD [ "bash", "/usr/src/myapp/run.sh" ]
