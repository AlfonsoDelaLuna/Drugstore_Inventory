# Drugstore Inventory System
It is an inventory management system designed for drugstores, providing a centralized platform to manage and track pharmaceutical products. It encompasses functionalities for monitoring stock levels, recording comprehensive product details (such as name, description, number of items, and expiration date), and generating various management reports.

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
To set up the Drugstore Inventory System on your local machine, follow these steps:
1.  **Prerequisites:**
    - Ensure you have a local web server environment installed, such as [XAMPP](https://www.apachefriends.org/index.html) or [WAMP](https://www.wampserver.com/en/). This provides Apache, PHP, and MySQL.

2.  **Download the Project:**
    - Clone or download this repository to your local machine.
    - Place the project folder inside the `htdocs` directory of your XAMPP installation (e.g., `c:/xampp/htdocs/Drugstore_Inventory`).

3.  **Database Setup:**
    - Start your Apache and MySQL services from the XAMPP control panel.
    - Open your web browser and navigate to `http://localhost/phpmyadmin`.
    - Create a new database named `inventory_db`.
    - Select the newly created database and go to the "Import" tab.
    - Click "Choose File" and select the `inventory_db.sql` file located in the root of the project directory.
    - Click "Go" to import the database structure and initial data.

4.  **Configuration:**
    - The database connection is configured in `db_connect.php`. By default, it's set up for a standard XAMPP installation (Host: `localhost`, User: `root`, Password: ``, Database: `inventory_db`). If your setup is different, you will need to modify this file accordingly.

5.  **Access the System:**
    - Open your web browser and navigate to `http://localhost/Drugstore_Inventory`.
    - You should now see the login page.

## Usage
Once the system is set up, you can start using it to manage your inventory.
1.  **Login:**
    - Navigate to the application's URL in your web browser.
    - Use the following default credentials to log in:
      - **Username:** `admin`
      - **Password:** `admin`
    - It is highly recommended to change the default password after your first login for security purposes.

2.  **Dashboard:**
    - After logging in, you will be directed to the main dashboard, which provides an overview of the inventory, including total products, low-stock items, and expired products.

3.  **Inventory Management:**
    - Navigate to the "Inventory" section to perform the following actions:
      - **View Products:** See a list of all products with their details.
      - **Add a New Product:** Click on the "Add New" button to open a form where you can enter the product's name, description, quantity, expiration date, and supplier.
      - **Edit a Product:** Click the "Edit" button next to a product to update its information.
      - **Delete a Product:** Click the "Delete" button to remove a product from the inventory.

4.  **Reporting:**
    - The "Reports" section allows you to generate and view various reports, such as:
      - **Stock Levels:** A complete list of all products and their current quantities.
      - **Low-Stock Alerts:** A report highlighting products that are below a certain stock threshold.
      - **Expired Products:** A list of all products that have passed their expiration date.
