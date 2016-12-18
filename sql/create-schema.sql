CREATE TABLE category (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  name           VARCHAR(255),
  migration_date DATETIME
);

CREATE TABLE rental_price (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  film_title     VARCHAR(255),
  amount         FLOAT,
  migration_date DATETIME
);

CREATE TABLE rent (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  film_title     VARCHAR(255),
  start_date     DATETIME,
  end_date       DATETIME,
  migration_date DATETIME
);

CREATE TABLE film (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  title           VARCHAR(255),
  description     TEXT,
  category_id     INT,
  release_year    YEAR,
  rental_price_id INT,
  rent_id         INT,

  CONSTRAINT category_fk FOREIGN KEY (category_id) REFERENCES category (id),
  CONSTRAINT rental_price_fk FOREIGN KEY (rental_price_id) REFERENCES rental_price (id),
  CONSTRAINT rent_fk FOREIGN KEY (rent_id) REFERENCES rent (id)
);

COMMIT;