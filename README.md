ShareToNeighbour — Beginner-Friendly User
Guide (Local Setup)
This guide explains how to run ShareToNeighbour on a local computer using XAMPP and
how to use the main features: registration, posting items, requesting items, chatting, reviews,
and the support chatbot.
1. Before You Start
To follow this guide, you should have:
A Windows laptop or PC (recommended for XAMPP)
XAMPP installed with Apache and MySQL
A web browser such as Chrome, Edge, or Firefox
An optional real email address if you want to receive password reset and noti cation
emails
2. Start XAMPP Services
Open the XAMPP Control Panel.
Click Start next to:
Apache
MySQL
When both indicators turn green, the local server is ready.
3. Place the Project in the Correct Folder
Find the XAMPP 
htdocs folder, usually located at 
Copy the project folder into 
C:\xampp\htdocs\.
htdocs so the nal path becomes:
C:\xampp\htdocs\ShareToNeighbour\
The project can then be accessed at:
http://localhost/ShareToNeighbour/
4. Import the Database
4.1 Open phpMyAdmin
Open the following address in a browser:
http://localhost/phpmyadmin
4.2 Import the SQL File
Open the Import tab.
Click Choose File and select the project's 
sql/ folder.
Click Go to start the import.
.sql le, commonly located in a 
database/ or
After the import is complete, the database tables should appear in phpMyAdmin.
If the SQL le cannot be found, search the project folder for les ending in 
5. Open the ShareToNeighbour Website
Try one of the following URLs in the browser:
If the project uses a 
public folder:
http://localhost/ShareToNeighbour/public/
Otherwise:
http://localhost/ShareToNeighbour/
The ShareToNeighbour homepage should now appear.
6. Create an Account
Click Join Community or Register.
Enter a name, email address, and password.
Using a real email address is recommended if the platform is expected to send:
Password reset links
Noti cation or alert emails
.sql.
If SMTP is not con gured on the computer, the platform may still work, but emails will
not be delivered.
7. Browse Furniture Near You
Click Browse Furniture from the menu.
View furniture listings near the saved address.
Open any listing to see its details and photos.
8. Post a Furniture Listing (Owner)
Make sure you are logged in before posting a listing.
Listing rules:
Title: at least 3 characters
Description: at least 10 characters
Category: sofa, table, chair, bed, shelf, desk, wardrobe, or other
Condition: like_new, good, fair, or needs_repair
Photo rules:
Minimum 1 and maximum 3 photos
Allowed formats: JPG, PNG, GIF, WEBP
Maximum size: 5 MB per photo
The rst photo becomes the cover photo
After submission, the listing becomes available to other users.
9. Request an Item (Requester)
Open a listing of interest.
Click the Request button.
The listing status changes to requested.
The owner can review and accept one request.
10. Complete an Exchange and Mark the Item as Taken (Owner)
After the item has been picked up or handed over:
The owner logs in and marks the item as taken.
Notes:
Items marked as taken are no longer available for further requests.
The records remain in the system for admin audit and to preserve transaction and review
history.
11. Leave a Review
A review can be left only after:
The request has been accepted
The item has been marked as taken by the owner
Review rules:
Rating: 1 to 5 stars
Comment: optional, up to around 500 characters
Photo: optional, following the same image rules as listing photos
One review per transaction per reviewer
12. Real-Time Chat / Messaging
Some versions of ShareToNeighbour include a small chat server for real-time messaging. If
messages do not appear instantly, the chat server may need to be started manually.
Open Terminal or Command Prompt in the project folder, for example:
cd C:\xampp\htdocs\ShareToNeighbour
Run the PHP WebSocket server:
php C:\xampp\htdocs\shareToNeighbour\ws-server.php
Keep the terminal window open while testing chat.
To test chat with two users:
Open Chrome and Edge, or use two di erent browsers
Log in as User 1 in one browser
Log in as User 2 in the other browser
Send messages and con rm they appear without manually refreshing the page
13. Use the Support Chatbot
Open the chatbot widget, usually located in the bottom corner of the page.
Type a question or issue.
Click Send or press Enter.
If a backend error appears:
The chatbot backend, often an Ollama model, may be slow or may time out when using
heavier models.
The following command can be used in Terminal:
ollama serve
14. Open the Site on a Phone (Same Wi-Fi)
14.1 Find the PC IP Address
On the Windows PC:
Open Command Prompt.
Run:
ipconfig
Look for the IPv4 Address, for example 
14.2 Open the Site on the Phone
In the phone browser, enter:
192.168.1.10.
http://192.168.1.10/ShareToNeighbour/public/
Replace the sample IP address with the actual IPv4 address of the computer.
Make sure that:
Apache is running in XAMPP
The PC and phone are connected to the same Wi-Fi network
The rewall is not blocking access
Quick Start Checklist
Start Apache and MySQL in XAMPP
Import the 
sharetoneighbou.sql le in phpMyAdmin
Open the site in the browser
Register and log in
Select the address from the suggestions
Post, request, accept, mark as taken, and review
Optionally start the chat server for real-time messaging
