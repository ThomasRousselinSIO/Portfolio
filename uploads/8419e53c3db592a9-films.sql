CREATE TABLE films (

    id INT AUTO_INCREMENT PRIMARY KEY,

    titre VARCHAR(255) NOT NULL,

    genre VARCHAR(100) NOT NULL,

    annee INT NOT NULL,

    note FLOAT NOT NULL,

    description TEXT,

    image TEXT
);
