ALTER TABLE film DROP FOREIGN KEY category_fk;
ALTER TABLE film DROP FOREIGN KEY rental_price_fk;
ALTER TABLE film DROP FOREIGN KEY rent_fk;
DROP TABLE film;
DROP TABLE rental_price;
DROP TABLE rent;
DROP TABLE category;