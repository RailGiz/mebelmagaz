<!DOCTYPE html>
<html>
<head>
    <title>Онлайн-магазин мебели</title>
    <style>
        .product {
            margin-bottom: 10px;
        }

        .cart {
            margin-top: 20px;
        }

        .cart table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart th, .cart td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .cart th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .total {
            margin-top: 10px;
            font-weight: bold;
        }

        .delete-button {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <h1>Онлайн-магазин мебели</h1>

    <?php
    // Подключение к базе данных
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "mebel_store";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения к базе данных: " . $conn->connect_error);
    }

    // Обработка нажатия кнопки "Добавить в корзину"
    if (isset($_POST['add_to_cart'])) {
        $furniture_id = $_POST['furniture_id'];
        $quantity = $_POST['quantity'];

        // Проверка, существует ли товар с заданным идентификатором
        $check_query = "SELECT * FROM furniture WHERE id = $furniture_id";
        $check_result = $conn->query($check_query);

        if ($check_result->num_rows > 0) {
            // Товар существует, проверяем, есть ли он уже в корзине
            $cart_query = "SELECT * FROM cart WHERE furniture_id = $furniture_id";
            $cart_result = $conn->query($cart_query);

            if ($cart_result->num_rows > 0) {
                // Товар уже в корзине, обновляем количество
                $cart_row = $cart_result->fetch_assoc();
                $cart_id = $cart_row['id'];
                $new_quantity = $cart_row['quantity'] + $quantity;

                $update_query = "UPDATE cart SET quantity = $new_quantity WHERE id = $cart_id";
                if ($conn->query($update_query) === TRUE) {
                    echo "Товар успешно добавлен в корзину.";
                } else {
                    echo "Ошибка: " . $conn->error;
                }
            } else {
                // Товара нет в корзине, добавляем новую запись
                $insert_query = "INSERT INTO cart (furniture_id, quantity) VALUES ($furniture_id, $quantity)";
                if ($conn->query($insert_query) === TRUE) {
                    echo "Товар успешно добавлен в корзину.";
                } else {
                    echo "Ошибка: " . $conn->error;
                }
            }
        } else {
            echo "Товар не найден.";
        }
    }

    // Обработка нажатия кнопки "Удалить"
    if (isset($_POST['delete_item'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = $_POST['quantity'];

        // Обновление количества товара в корзине
        $update_query = "UPDATE cart SET quantity = quantity - $quantity WHERE id = $cart_id";
        if ($conn->query($update_query) === TRUE) {
            // Проверка, если количество стало нулевым, удаляем товар из корзины
            $delete_query = "DELETE FROM cart WHERE quantity = 0";
            $conn->query($delete_query);
            echo "Товар успешно удален из корзины.";
        } else {
            echo "Ошибка: " . $conn->error;
        }
    }

    // Обработка нажатия кнопки "Удалить все"
    if (isset($_POST['delete_all'])) {
        // Удаление всех товаров из корзины
        $delete_all_query = "DELETE FROM cart";
        if ($conn->query($delete_all_query) === TRUE) {
            echo "Все товары успешно удалены из корзины.";
        } else {
            echo "Ошибка: " . $conn->error;
        }
    }

    // Вывод списка товаров
    $products_query = "SELECT * FROM furniture";
    $products_result = $conn->query($products_query);

    if ($products_result->num_rows > 0) {
        echo "<h2>Список товаров:</h2>";

        while ($row = $products_result->fetch_assoc()) {
            $furniture_id = $row['id'];
            $furniture_name = $row['name'];
            $furniture_price = $row['price'];

            echo "<div class='product'>";
            echo "<span>$furniture_name (Цена: $furniture_price)</span>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='furniture_id' value='$furniture_id'>";
            echo "<input type='number' name='quantity' min='1' value='1'>";
            echo "<input type='submit' name='add_to_cart' value='Добавить в корзину'>";
            echo "</form>";
            echo "</div>";
        }
    } else {
        echo "Нет доступных товаров.";
    }

    // Вывод корзины
    $cart_query = "SELECT c.id AS cart_id, c.quantity, f.name, f.price FROM cart c
                   INNER JOIN furniture f ON c.furniture_id = f.id";
    $cart_result = $conn->query($cart_query);

    if ($cart_result->num_rows > 0) {
        echo "<div class='cart'>";
        echo "<h2>Корзина:</h2>";
        echo "<table>";
        echo "<tr><th>Название</th><th>Цена</th><th>Количество</th><th>Удалить</th></tr>";

        while ($cart_row = $cart_result->fetch_assoc()) {
            $cart_id = $cart_row['cart_id'];
            $furniture_name = $cart_row['name'];
            $furniture_price = $cart_row['price'];
            $quantity = $cart_row['quantity'];
            $subtotal = $furniture_price * $quantity;

            echo "<tr>";
            echo "<td>$furniture_name</td>";
            echo "<td>$furniture_price</td>";
            echo "<td>$quantity</td>";
            echo "<td>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='cart_id' value='$cart_id'>";
            echo "<input type='number' name='quantity' min='1' max='$quantity' value='1'>";
            echo "<input type='submit' name='delete_item' value='Удалить' class='delete-button'>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";

        // Кнопка "Удалить все"
        echo "<form method='post'>";
        echo "<input type='submit' name='delete_all' value='Удалить все'>";
        echo "</form>";
    } else {
        echo "<div class='cart'>";
        echo "<h2>Корзина:</h2>";
        echo "<p>Корзина пуста.</p>";
        echo "</div>";
    }

    // Общая стоимость товаров в корзине
    $total_query = "SELECT SUM(f.price * c.quantity) AS total_price FROM cart c
                    INNER JOIN furniture f ON c.furniture_id = f.id";
    $total_result = $conn->query($total_query);
    $total_row = $total_result->fetch_assoc();
    $total_price = $total_row['total_price'];

    echo "<div class='total'>";
    echo "<p>Общая стоимость: $total_price</p>";
    echo "</div>";

    // Закрытие соединения с базой данных
    $conn->close();
    ?>

</body>
</html>
