FROM raspbian/stretch

RUN apt-get update \
    && apt-get install -y python3 python3-pip build-essential tk-dev libncurses5-dev libncursesw5-dev libreadline6-dev libdb5.3-dev libgdbm-dev libsqlite3-dev libssl-dev libbz2-dev libexpat1-dev liblzma-dev zlib1g-dev \
    && pip3 install Adafruit_DHT sendgrid twilio