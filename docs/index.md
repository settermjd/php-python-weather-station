# Raspberry Pi Weather Station Documentation

## Installation & setup

## Prerequisites

To use this project, you need the following:

- A Raspberry Pi (ideally a 3B+ or newer) running [Raspberry Pi OS](https://www.raspberrypi.com/software/) (formerly Raspbian), powered by either a USB cable or wall adapter.
- A [DHT11 temperature and humidity sensor](https://learn.adafruit.com/dht), a GPIO Breadboard, four jumper wires, and a 10K Ohm pull up resistor.

### Notes 

I chose the DHT11 sensor as, while it's a little slow and its accuracy isn't as high as other sensors, it doesn't cost much and is readily available. Feel free to use a sensor with greater range and accuracy, such as the [DHT22](http://www.adafruit.com/products/385) or [AM2302](https://www.adafruit.com/product/393), if you'd prefer and are more experienced.

If you're just getting started, I recommend getting a starter kit, such as the [Freenove Ultimate Starter Kit](https://www.amazon.com/Freenove-Raspberry-Processing-Tutorials-Components/dp/B06W54L7B5/ref=asc_df_B06W54L7B5/?tag=googshopde-21&linkCode=df0&hvadid=310638483583&hvpos=&hvnetw=g&hvrand=6600907201633910215&hvpone=&hvptwo=&hvqmt=&hvdev=c&hvdvcmdl=&hvlocint=&hvlocphy=9042616&hvtargid=pla-351541905035&psc=1&th=1&psc=1&tag=&ref=&adgrpid=63367893073&hvpone=&hvptwo=&hvadid=310638483583&hvpos=&hvnetw=g&hvrand=6600907201633910215&hvqmt=&hvdev=c&hvdvcmdl=&hvlocint=&hvlocphy=9042616&hvtargid=pla-351541905035). It has the sensor, GPIO Breadboard, connecting ribbon, and everything else that you'll need. Also, if you don't have a Raspberry Pi, yet, [I recommend this starter kit](https://thepihut.com/collections/featured-products/products/raspberry-pi-starter-kit).

### Clone and set up the project

Run the following instructions to clone the application's source, and install the PHP and frontend dependencies.

```bash
git clone git@github.com:settermjd/php-python-weather-station.git
cd php-python-weather-station
composer install
npm install
```

### Add Crontab record for the Python script

Next, on the Raspberry Pi, add the Python script that reads data from the sensor and inserts it in to the SQLite database. Run the command below to open the Crontab editor for the user that will run the Python script.

```bash
crontab -e
```

Then, add the following snippet to the bottom of the file, save the file, and close the editor.

```bash
* * * * * cd <installation directory> && python3 bin/dht11-sensor-reader.py
```

**Note:** The user that runs the Python script **must** be in the `GPIO` group, otherwise it won't be able to read data from the sensor. So make sure that the user is added to that group.

### Wire up the GPIO Board and connect it to the Raspberry Pi

For full instructions on wiring up the GPIO board, and setting up the Raspberry Pi - especially if this is your first time using them - step through [the tutorial that I wrote](https://www.twilio.com/blog/build-weather-station-with-php-python-raspberry-pi) for the Twilio blog.
It contains all that you need to know.

### Add the required environment variables

The application needs a number of environment variables to be available. To set them, copy _.env.example_ in the top-level directory of the project as _.env_. Then, set each of the variables, and the application should work as expected.

## Running the application

### Sending status notifications

To send a status notification, add the following command to the applicable user's Crontab on the Raspberry Pi, or run it manually, changing the dates as appropriate.

```bash
curl http://localhost/daily-summary/2022-02-11/2022-02-12
```