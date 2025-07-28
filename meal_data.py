import requests
import mysql.connector
import traceback
import json

USERNAME = "8166"
PASSWORD = "028c1cb6-c2e7-4ce2-9ace-1bba8aec92a6"
API_URL = "https://api.worldota.net/api/b2b/v3/hotel/static/"

DB_NAME = "rezervo24_mjellma"
DB_USER = "rezervo24_mjellma"
DB_PASSWORD = "4gjp{qW~*]lZ"
DB_HOST = "localhost"
DB_PORT = "3306"

def fetch_meal_types():
    print("Fetching meal types from API...")
    response = requests.get(API_URL, auth=(USERNAME, PASSWORD))

    if response.status_code != 200:
        raise Exception(f"Failed to fetch static data: {response.text}")

    data = response.json()
    meals = data.get("data", {}).get("meals", [])
    print("Sample meal:", json.dumps(meals[0], indent=2, ensure_ascii=False))  # Debug
    return meals

def store_meal_types(meals):
    print("Connecting to database...")
    conn = mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME,
        port=DB_PORT
    )
    cursor = conn.cursor()

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS meal_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE,
            locale JSON
        )
    """)

    for meal in meals:
        name = meal.get("name")
        locale = json.dumps(meal.get("locale", {}), ensure_ascii=False)

        cursor.execute("""
            INSERT INTO meal_types (name, locale)
            VALUES (%s, %s)
            ON DUPLICATE KEY UPDATE
                locale = VALUES(locale)
        """, (name, locale))

    conn.commit()
    cursor.close()
    conn.close()
    print("Meal types stored successfully.")

if __name__ == "__main__":
    try:
        meals = fetch_meal_types()
        store_meal_types(meals)
    except Exception as e:
        print("An error occurred:")
        traceback.print_exc()
