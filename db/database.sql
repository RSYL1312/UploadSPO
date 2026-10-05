CREATE DATABASE IF NOT EXISTS db_spo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_spo;

CREATE TABLE unit_spo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE,
  folder VARCHAR(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE unit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE dokumen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_spo_id INT NOT NULL,
  nama_dokumen VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (unit_spo_id) REFERENCES unit_spo(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE dokumen_unit (
  dokumen_id INT NOT NULL,
  unit_id INT NOT NULL,
  PRIMARY KEY (dokumen_id, unit_id),
  FOREIGN KEY (dokumen_id) REFERENCES dokumen(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES unit(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO unit_spo (nama) VALUES
('Anestesi'),('Casemix'),('CSSD'),('Farmasi'),('Fisioterapi'),('Gizi'),('HD'),('HPK'),('ICU'),('IGD'),
('IT'),('Kamar Bedah'),('Kamar Jenazah'),('KE'),('Kebidanan'),('Kemoterapi'),('Keuangan'),('Laboratorium'),
('Linen'),('Marketing'),('MR'),('NICU'),('PAN RS'),('PROGNAS'),('Radiologi'),('Rawat Jalan'),('Rawat Inap'),
('SDM'),('SKP'),('Umum'),('Yanmed');
UPDATE unit_spo SET folder = REPLACE(LOWER(nama),' ','-');

-- Unit terkait awal (sama dengan daftar unit_spo, bisa ditambah lewat menu Kelola Unit)
INSERT INTO unit (nama) SELECT nama FROM unit_spo;
