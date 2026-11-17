CREATE DATABASE IF NOT EXISTS hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel;
CREATE TABLE rooms(id INT AUTO_INCREMENT PRIMARY KEY, category ENUM('Стандарт','Студия','Люкс') NOT NULL, price INT NOT NULL CHECK(price>0),
  seats INT NOT NULL CHECK(seats>0), area DECIMAL(5,1) NOT NULL, image VARCHAR(100) NOT NULL) ENGINE=InnoDB;
CREATE TABLE amenities(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE room_amenities(room_id INT NOT NULL, amenity_id INT NOT NULL, PRIMARY KEY(room_id, amenity_id),
  FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE, FOREIGN KEY(amenity_id) REFERENCES amenities(id)) ENGINE=InnoDB;
CREATE TABLE bookings(id INT AUTO_INCREMENT PRIMARY KEY, room_id INT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL,
  phone VARCHAR(20) NOT NULL, email VARCHAR(100) NOT NULL, check_in DATE NOT NULL, check_out DATE NOT NULL,
  status ENUM('Новая','Одобрена','Отклонена') NOT NULL DEFAULT 'Новая', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE login_attempts(id INT AUTO_INCREMENT PRIMARY KEY, ip VARCHAR(45) NOT NULL, ts INT NOT NULL) ENGINE=InnoDB;
INSERT INTO amenities(name) VALUES ('Полный санузел'),('Двуспальная кровать'),('Телевизор'),('Кухонный уголок'),('Холодильник'),('Двухкомнатный'),('Кондиционер');
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Стандарт',3000,2,18,'standard-1.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (1,1),(1,2),(1,3);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Стандарт',3400,2,20,'standard-2.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (2,1),(2,2),(2,3);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Стандарт',3800,3,24,'standard-3.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (3,1),(3,2),(3,3);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Студия',4600,2,28,'studio-1.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (4,4),(4,1),(4,2),(4,3),(4,5);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Студия',5400,3,32,'studio-2.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (5,4),(5,1),(5,2),(5,3),(5,5);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Студия',6200,4,36,'studio-3.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (6,4),(6,1),(6,2),(6,3),(6,5);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Люкс',8500,2,50,'lux-1.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (7,6),(7,1),(7,2),(7,3),(7,5),(7,7);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Люкс',11500,3,62,'lux-2.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (8,6),(8,1),(8,2),(8,3),(8,5),(8,7);
INSERT INTO rooms(category,price,seats,area,image) VALUES ('Люкс',15000,4,80,'lux-3.jpg');
INSERT INTO room_amenities(room_id,amenity_id) VALUES (9,6),(9,1),(9,2),(9,3),(9,5),(9,7);
