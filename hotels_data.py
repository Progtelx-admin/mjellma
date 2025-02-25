import requests
import zstandard as zstd
import json
import mysql.connector
import traceback
import io

USERNAME = "8166"
PASSWORD = "028c1cb6-c2e7-4ce2-9ace-1bba8aec92a6"
API_URL = "https://api.worldota.net/api/b2b/v3/hotel/info/dump/"

DB_NAME = "mjellma"
DB_USER = "root"
DB_PASSWORD = ""
DB_HOST = "localhost"
DB_PORT = "3306"

def fetch_dump_url():
    """
    Fetch the dump URL from the API.
    """
    response = requests.post(
        API_URL,
        auth=(USERNAME, PASSWORD),
        json={"language": "en"},
    )

    if response.status_code != 200:
        raise Exception(f"Failed to fetch dump URL: {response.text}")

    data = response.json()
    dump_url = data.get("data", {}).get("url")

    if not dump_url:
        raise Exception("Dump URL not found in API response.")

    return dump_url

def replace_image_size(image_url):
    """
    Replace the {size} placeholder in image URLs with 1080x1920.
    """
    return image_url.replace("{size}", "1080x1920") if image_url else image_url

def decompress_and_store_data(dump_url):
    """
    Fetch, decompress, and store hotel data directly into the database.
    """
    # Fetch the compressed .zst file
    print(f"Fetching data from {dump_url}...")
    response = requests.get(dump_url, stream=True)

    if response.status_code != 200:
        raise Exception(f"Failed to download compressed data: {response.text}")

    # Decompress the .zst file
    print("Decompressing data...")
    decompressor = zstd.ZstdDecompressor()
    decompressed_data = decompressor.stream_reader(response.raw)

    # Wrap the binary stream in a text wrapper
    text_stream = io.TextIOWrapper(decompressed_data, encoding="utf-8")

    # Connect to the MySQL/MariaDB database
    conn = mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME,
        port=DB_PORT
    )
    cursor = conn.cursor()

    # Create tables if not exists
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id VARCHAR(255) UNIQUE,
            hid VARCHAR(255) UNIQUE,
            name TEXT,
            address TEXT,
            latitude TEXT,
            longitude TEXT,
            star_rating FLOAT
        )
    """)

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS hotel_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id VARCHAR(255),
            image_url TEXT,
            category_slug VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id)
        )
    """)

    # Process and store data line by line
    print("Storing data into the database...")
    for line in text_stream:
        if not line.strip():
            continue

        try:
            hotel = json.loads(line)

            # Skip invalid entries
            if "id" not in hotel or "hid" not in hotel:
                print(f"Skipping invalid entry: {line}")
                continue


            latitude = hotel.get("latitude")
            longitude = hotel.get("longitude")
            star_rating = hotel.get("star_rating")
            image_urls = hotel.get("images", [])
            images_ext = hotel.get("images_ext", [])

            cursor.execute("""
                INSERT INTO hotels (hotel_id, hid, name, address, latitude, longitude, star_rating)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                address = VALUES(address),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                star_rating = VALUES(star_rating)
            """, (hotel["id"], hotel["hid"], hotel.get("name"), hotel.get("address"), latitude, longitude, star_rating))


            for image_url in image_urls:
                try:
                    fixed_image_url = replace_image_size(image_url)
                    cursor.execute("""
                        INSERT INTO hotel_images (hotel_id, image_url, category_slug)
                        VALUES (%s, %s, %s)
                    """, (hotel["id"], fixed_image_url, None))
                except Exception as img_ex:
                    print(f"Failed to insert image {image_url} for hotel {hotel['id']}: {img_ex}")

            for image in images_ext:
                try:
                    fixed_image_url = replace_image_size(image["url"])
                    cursor.execute("""
                        INSERT INTO hotel_images (hotel_id, image_url, category_slug)
                        VALUES (%s, %s, %s)
                    """, (hotel["id"], fixed_image_url, image.get("category_slug")))
                except Exception as img_ex:
                    print(f"Failed to insert extended image {image['url']} for hotel {hotel['id']}: {img_ex}")

            conn.commit()

        except Exception as ex:
            print(f"Error processing line: {line}")
            print(f"Exception: {ex}")

    # Close the connection
    cursor.close()
    conn.close()
    print("Data stored successfully.")

if __name__ == "__main__":
    try:
        dump_url = fetch_dump_url()
        print(f"Dump URL: {dump_url}")

        decompress_and_store_data(dump_url)

    except Exception as e:
        print("An error occurred:")
        traceback.print_exc()
