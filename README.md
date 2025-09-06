# Drugstore Inventory System

This system is designed to manage and track inventory for a drugstore.

## Purpose

This Drugstore Inventory System is designed to streamline the management of pharmaceutical products and related items within a drugstore setting. It provides a centralized platform for monitoring stock levels, tracking product information (such as name, description, supplier, and expiration date), and generating comprehensive reports for informed decision-making in inventory management. The system aims to improve efficiency, reduce errors, and optimize stock levels to meet customer demand while minimizing waste.

## Problems Encountered

The problems encoutered is when the nurse do inventory, it is usually paper-bases.

## Technologies Used

- **PHP:** The system is primarily built using PHP, a server-side scripting language, for handling backend logic and database interactions.
- **MySQL:** MySQL is used as the relational database management system to store and manage inventory data, user information, and other relevant details.
- **HTML:** HTML (Hypertext Markup Language) is used to structure the content and layout of the web pages.
- **CSS:** CSS (Cascading Style Sheets) is used to style the web pages, ensuring a visually appealing and user-friendly interface.
- **JavaScript:** JavaScript is used to add interactivity and dynamic functionality to the web pages, enhancing the user experience.

## Setup Instructions

1.  **Database Setup:**
    - Import the `inventory_db.sql` file into your MySQL database using a tool like phpMyAdmin or MySQL Workbench. This will create the necessary tables and initial data for the system.
2.  **Configuration:**
    - Open the `db_connect.php` file in a text editor.
    - Modify the database connection parameters (host, username, password, database name) to match your MySQL server configuration.
3.  **Deployment:**
    - Deploy the entire project folder to a PHP-enabled web server, such as Apache.
    - Ensure that the web server is configured to correctly handle PHP files.
4.  **Access:**
    - Open your web browser and navigate to the URL where you deployed the system (e.g., `http://localhost/Drugstore_Inventory`).

## Usage

1.  **Access:**
    - Open your web browser and navigate to the system's URL.
2.  **Login:**
    - Enter your username and password on the login page to access the system's features.
3.  **Inventory Management:**
    - Use the navigation menu to access the inventory management section.
    - From there, you can:
      - **Add Items:** Add new products to the inventory, providing details such as name, description, expiration date, price, and quantity.
      - **Edit Items:** Modify the details of existing products, such as updating prices or quantities.
      - **Delete Items:** Remove products from the inventory.
4.  **Reporting:**
    - Access the reporting section to generate reports on stock levels, low-stock items, expired items, and inventory history.

## Additional Information

For more detailed information, refer to the system documentation or contact the system administrator.
