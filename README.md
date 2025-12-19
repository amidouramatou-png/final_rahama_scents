# final_rahama_scents
Rahama Scents – Luxury Fragrance E-Commerce Platform
Rahama Scents is a full-featured e-commerce web application built for selling luxury handcrafted fragrances. It includes three integrated role-based dashboards: Admin, Customer, and Delivery Staff, providing a complete end-to-end solution for online fragrance sales, inventory management, order fulfillment, and real-time analytics.
Tagline: Luxury that soothes the soul
## Table of Contents
•	Features
•	Project Structure
•	Technology Stack
•	Database Schema
•	Installation & Setup
•	Roles & Dashboards

## Features
### Customer Features
•	Browse products with images, prices, and descriptions
•	Add to cart with real-time quantity updates
•	Secure checkout with delivery details (name, phone, address)
•	Multiple payment options (Card, Mobile Money, Cash on Delivery)
•	View order history and status tracking
•	User profile management

### Admin Dashboard
•	Add, edit, delete products (with image upload)
•	Real-time sales analytics (monthly revenue, top products, category breakdown)
•	Inventory management with low-stock alerts
•	Full order management (view, update status, export reports)
•	Customer overview and message inbox
•	Approve/reject delivery staff registrations
### Delivery Dashboard
•	Real-time notifications for new orders (simulated via email/webhook)
•	View pending deliveries with customer address and contact
•	Accept orders and mark as delivered
•	Personal profile with vehicle type and photo
### General Features
•	Role-based authentication and access control
•	Responsive design with modern UI
•	Session-based shopping cart
•	Secure password hashing
•	Contact form with message storage

## Project Structure
### text
rahama_scents/
•	admin.php                   # Main Admin Dashboard
•	analytics.php               # Sales charts & stats
•	 cart.php                    # Shopping cart
•	checkout.php                # Checkout form
•	config.php                  # Database connection & helpers
•	customer_dashboard.php      # Customer home
•	delivery_dashboard.php      # Delivery staff panel
•	delivery_login.php
•	 delivery_register.php
•	delivery_profile.php
•	inventory.php               # Stock management
•	 login.php                   # Unified login
•	logout.php
•	manage_delivery_staff.php   # Admin delivery staff approval
•	my_orders.php               # Customer order history
•	orders.php                  # Admin order management
•	 shop.php                    # Product listing
•	signup.php                  # Customer registration
•	admin.css / admin.js        # Admin panel styling & scripts
•	checkout.css / checkout.js
•	customer_dashboard.css / .js
•	 RahamaScents.css / .js      # Main site styling
•	uploads/                    # Product & delivery image
•	database/                   # SQL schema (rahama_scents.sql)

### Technology Stack
•	Frontend: HTML5, CSS3, JavaScript, Font Awesome
•	Backend: PHP 7+
•	Database: MySQL
•	Libraries: Chart.js (analytics), Session management
•	Server: Apache / XAMPP / Local PHP server
Database Schema
Key tables:
•	users – Customers & admins
•	products – Fragrances with stock & images
•	orders – Order details & status
•	order_items – Products in each order
•	messages – Contact form submissions
•	delivery_staff – Delivery personnel accounts
Installation & Setup
Prerequisites
•	XAMPP / WAMP / LAMP stack
•	PHP 7.4+
•	MySQL
Default Accounts (for testing)
•	Admin: Create via signup and manually set role = 'admin' in database for security
•	Customer: Register normally at signup.php
•	Delivery Staff: Register at delivery_register.php  and will be Approve via admin panel
Roles & Dashboards
Role	Login Page	Dashboard	Key Actions
CUSTOMER	login.php	customer_dashboard.php	Shop, cart, checkout, track orders
ADMIN 	login.php	admin.php	Manage products, view analytics, orders

DELIVERY Staff	delivery_login.php	delivery_dashboard.php	Accept & complete deliveries

			
## Future Enhancements
•	Integrate real payment gateways (Paystack, Flutterwave)
•	Email/SMS notifications using PHPMailer
•	Product search & filtering
•	Customer reviews & ratings
•	Mobile-responsive improvements
•	REST API for mobile app
•	Multi-language support


