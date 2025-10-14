# Drugstore Inventory System

It is an inventory management solution designed for drugstores, providing a centralized platform to manage and track pharmaceutical products and related items. It encompasses functionalities for monitoring stock levels, recording comprehensive product details (such as name, description, supplier, and expiration date), and generating various management reports.

## Purpose

The purpose of this system is to enhance operational efficiency and accuracy within drugstores by streamlining inventory processes. It aims to achieve this by:

1. Optimizing Stock Management: Ensuring optimal stock levels to meet demand while minimizing overstocking and waste.
2. Reducing Errors: Automating tracking to significantly decrease manual errors in inventory records.
3. Informing Decisions: Providing data-driven reports for better planning and procurement.

## Problems Encountered

The current inventory management practices within the drugstore face several critical challenges that hinder efficiency, accuracy, and profitability. These issues directly underscore the need for the proposed system and its stated purpose:

1. Inefficient Paper-Based Processes:
	- Problem: The reliance on manual, paper-based recording makes the entire stock monitoring process highly inefficient, disorganized, and susceptible to physical damage or loss of critical inventory data.
	- Connection to Purpose: This directly impedes the system's purpose of "optimizing stock management" and "reducing errors" as manual methods are inherently slow and error-prone.

2. Prevalence of Incorrect Information:
	- Problem: Manual entry leads to frequent errors in recording vital product details like quantity, supplier, and expiration dates, resulting in unreliable inventory data that cannot be trusted for operational decisions.
	- Connection to Purpose: This directly undermines the system's goals of "reducing errors" and making "data-driven decisions," as the foundation of those goals (accurate data) is missing.


3. Difficulty in Real-time Stock Monitoring:
	- Problem: Without a centralized, automated system, tracking available products is an extremely time-consuming endeavor. This often leads to inaccurate stock visibility and significant delays in fulfilling customer orders or restocking, impacting service quality.
	- Connection to Purpose: This is a core challenge that the system's purpose of "optimizing stock management" seeks to overcome by providing real-time, centralized visibility.

4. Inadequate Expiration Date Tracking:
	- Problem: Medicines and other perishable items frequently remain unsupervised, resulting in expired stock that represents significant financial waste and poses potential health risks if unknowingly dispensed.
	- Connection to Purpose: This problem highlights the urgent need for the system's capability to track "detailed product information (including expiration dates)" to minimize waste and ensure product safety.

5. Lack of Accurate & Timely Reports for Decision-Making:
	- Problem: Manually generating reliable and comprehensive reports for inventory analysis is a tedious, labor-intensive, and often inaccurate process. This severely limits management's ability to make informed procurement, stocking, and business decisions.
	- Connection to Purpose: This problem directly demonstrates the necessity for the system's purpose of "informing decisions" through robust and accurate reporting capabilities.

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
