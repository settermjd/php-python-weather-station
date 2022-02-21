import Adafruit_DHT
import os
import random
import sqlite3
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

gpio_pin = 17
database_file = 'data/database/weather_station.sqlite'

humidity, temperature = Adafruit_DHT.read_retry(database_file, gpio_pin)

if humidity is not None and temperature is not None:
    connection = create_connection('./data/database/weather_station.sqlite')
    add_weather_data = (f'INSERT INTO weather_data (temperature, humidity) VALUES'
                        '({round(temperature, 2)}, {round(humidity, 2)});')
    execute_query(connection, add_weather_data)
