import Adafruit_DHT
import sqlite3
import random
import time
from sqlite3 import Error

def create_connection(path):
    connection = None
    try:
        connection = sqlite3.connect(path)
        print("Connection to SQLite DB successful")
    except Error as e:
        print(f"The error '{e}' occurred")

    return connection

def execute_query(connection, query):
    cursor = connection.cursor()
    try:
        cursor.execute(query)
        connection.commit()
        print("Query executed successfully")
    except Error as e:
        print(f"The error '{e}' occurred")

# Use the DHT11 temperature and humidity sensor
sensor = Adafruit_DHT.DHT11

# The sensor is connected to GPIO 17
pin = 17

# Attempt to read in the humidity and temperature from the sensor
humidity, temperature = Adafruit_DHT.read_retry(sensor, pin)

if humidity is not None and temperature is not None:
    connection = create_connection('./weather_station.sqlite')
    add_weather_data = f"INSERT INTO weather_data (temperature, humidity, timestamp) VALUES ({round(temperature,2)}, {round(humidity,2)}, {round(timestamp,2)});"
    execute_query(connection, add_weather_data)