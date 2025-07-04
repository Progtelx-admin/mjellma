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
BATCH_SIZE = 100

def fetch_dump_url():
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
    return image_url.replace("{size}", "1080x1920") if image_url else image_url

def insert_batch(cursor, hotels, images):
    if hotels:
        cursor.executemany(
            """
            INSERT INTO hotels (
                hotel_id, hid, name, address, latitude, longitude, star_rating,
                metapolicy_struct, metapolicy_extra_info
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                address = VALUES(address),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                star_rating = VALUES(star_rating),
                metapolicy_struct = VALUES(metapolicy_struct),
                metapolicy_extra_info = VALUES(metapolicy_extra_info)
            """,
            hotels,
        )
    if images:
        cursor.executemany(
            """
            INSERT INTO hotel_images (hotel_id, image_url, category_slug)
            VALUES (%s, %s, %s)
            """,
            images,
        )

def decompress_and_store_data(dump_url):
    print(f"Fetching data from {dump_url}...")
    response = requests.get(dump_url, stream=True)
    if response.status_code != 200:
        raise Exception(f"Failed to download compressed data: {response.text}")

    decompressor = zstd.ZstdDecompressor()
    decompressed_data = decompressor.stream_reader(response.raw)
    text_stream = io.TextIOWrapper(decompressed_data, encoding="utf-8")

    conn = mysql.connector.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME, port=DB_PORT
    )
    cursor = conn.cursor()

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id VARCHAR(255) UNIQUE,
            hid VARCHAR(255) UNIQUE,
            name TEXT,
            address TEXT,
            latitude TEXT,
            longitude TEXT,
            star_rating FLOAT,
            metapolicy_struct JSON,
            metapolicy_extra_info JSON
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

    hotels_batch = []
    images_batch = []

    print("Storing data into the database in batches...")
    for line in text_stream:
        if not line.strip():
            continue

        try:
            hotel = json.loads(line)
            if "id" not in hotel or "hid" not in hotel:
                print(f"Skipping invalid entry: {line}")
                continue

            hotels_batch.append(
                (
                    hotel["id"],
                    hotel["hid"],
                    hotel.get("name"),
                    hotel.get("address"),
                    hotel.get("latitude"),
                    hotel.get("longitude"),
                    hotel.get("star_rating"),
                    json.dumps(hotel.get("metapolicy_struct")) if hotel.get("metapolicy_struct") else None,
                    json.dumps(hotel.get("metapolicy_extra_info")) if hotel.get("metapolicy_extra_info") else None
                )
            )

            for image_url in hotel.get("images", []):
                images_batch.append((hotel["id"], replace_image_size(image_url), None))

            for image in hotel.get("images_ext", []):
                images_batch.append((hotel["id"], replace_image_size(image["url"]), image.get("category_slug")))

            if len(hotels_batch) >= BATCH_SIZE:
                insert_batch(cursor, hotels_batch, images_batch)
                conn.commit()
                hotels_batch.clear()
                images_batch.clear()
                print("Inserted a batch of 100 records...")

        except Exception as ex:
            print(f"Error processing line: {line}")
            print(f"Exception: {ex}")

    if hotels_batch or images_batch:
        insert_batch(cursor, hotels_batch, images_batch)
        conn.commit()
        print("Inserted the remaining records...")

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
