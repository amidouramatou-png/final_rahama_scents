<?php
// Database connection (adjust as needed)
$conn = new mysqli("localhost", "root", "", "rahama_scents");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$sql = "SELECT * FROM `products`;";
// Handle add product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = $conn->real_escape_string($_POST['description']);

    $target_dir = "uploads";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $image_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO products (name, category, price, stock, description, image) 
                VALUES ('$name', '$category', $price, $stock, '$description', '$target_file')";

        if ($conn->query($sql) === TRUE) {
            echo "<div class='success'>Product added successfully!</div>";
        } else {
            echo "<div class='error'>Error: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='error'>Failed to upload image.</div>";
    }
}

// Fetch all products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1, h2 { color:#c9a86a; text-align: center; }
        form { margin: 30px 0; padding: 20px; background: #f9f3ff; border-radius: 10px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            background: #c9a86a;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        button:hover { background: #c9a86a; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #c9a86a; color: white; }
        tr:hover { background: #f8f0ff; }
        img { max-width: 80px; height: auto; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin: 10px 0; }
        #preview { max-width: 200px; margin-top: 10px; border-radius: 8px; display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>Manage Products - Rahama Scents</h1>
    <div class="logo">
        <img src="Logo2.jpg" alt="Rahama's Scents" height="400">
    </div>

    <h2>Add New Product</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" required>

        <label>Category</label>
        <select name="category" required>
            <option value="floral">Floral</option>
            <option value="fresh">Fresh</option>
            <option value="woody">Woody</option>
            <option value="citrus">Citrus</option>
            <option value="oriental">Oriental</option>
            <option value="gourmand">Gourmand</option>
        </select>

        <label>Price (GHC)</label>
        <input type="number" step="0.01" name="price" required>

        <label>Stock</label>
        <input type="number" name="stock" required>

        <label>Description</label>
        <textarea name="description" rows="3"></textarea>

        <label>Product Image</label>
        <input type="file" name="image" id="image" accept="image/*" required>
        <img id="preview" src="" alt="Preview">

        <button type="submit" name="add_product">Add Product</button>
    </form>

    <h2>Product List</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Description</th>
            <th>Image</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= ucfirst($row['category']) ?></td>
            <td>$<?= number_format($row['price'], 2) ?></td>
            <td><?= $row['stock'] ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><img src="<?= $row['image'] ?>" alt="Product"></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
    // Live image preview when selecting a file
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>

<?php $conn->close(); ?>