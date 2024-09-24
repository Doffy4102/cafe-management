<?php

include 'components/connect.php';

session_start();

if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
} else {
   $user_id = '';
   header('location:home.php');
   exit;
};

if (isset($_POST['submit'])) {

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $method = $_POST['method'];
   $method = filter_var($method, FILTER_SANITIZE_STRING);
   $address = $_POST['address'];
   $address = filter_var($address, FILTER_SANITIZE_STRING);
   $total_products = $_POST['total_products'];
   $total_price = $_POST['total_price'];

   // Check if the cart has items
   $check_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
   $check_cart->execute([$user_id]);


   if ($check_cart->rowCount() > 0) {

      if ($address == '') {
         $message[] = 'Please add your address!';
      } else {

         // Insert order into the database
         $insert_order = $conn->prepare("INSERT INTO orders(user_id, name, number, email, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price]);


         // Clear the cart after successful order
         $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
         $delete_cart->execute([$user_id]);

         $message[] = 'Order placed successfully!';
      }
   } else {
      $message[] = 'Your cart is empty!';
   }
}

// // Voucher application logic
// if (isset($_POST['apply_voucher'])) {
//    $grand_total = $_POST['grand_total'];
//    $voucher_code = $_POST['voucher_code'];

//    if ($voucher_code == 'DISCOUNT600') {
//       $grand_total = $grand_total-100; // Apply a discount of ₹100
//       $message[] = 'Voucher applied successfully! ₹100 discount on total price.';
//    } else {
//       $message[] = 'Invalid voucher code!';
//    }
// }

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>

<body>

   <!-- header section starts  -->
   <?php include 'components/user_header.php'; ?>
   <!-- header section ends -->

   <div class="heading">
      <h3>Checkout</h3>
      <p><a href="home.php">Home</a> <span> / Checkout</span></p>
   </div>

   <section class="checkout">

      <h1 class="title">Order Summary</h1>
      <form action="" method="post">

         <div class="cart-items">
            <h3>Cart Items</h3>
            <?php
            $grand_total = 0;
            $cart_items[] = '';
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if ($select_cart->rowCount() > 0) {
               while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                  $cart_items[] = $fetch_cart['name'] . ' (' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ') - ';
                  $total_products = implode($cart_items);
                  $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
            ?>
                  <p><span class="name"><?= $fetch_cart['name']; ?></span><span class="price">Rs<?= $fetch_cart['price']; ?> x <?= $fetch_cart['quantity']; ?></span></p>
            <?php
               }
            } else {
               echo '<p class="empty">Your cart is empty!</p>';
            }
            ?>
            <p class="grand-total"><span class="name">Grand Total:</span><span class="price">Rs<?= $grand_total; ?></span></p>
            <a href="cart.php" class="btn">View Cart</a>
            <button class="btn" onclick="applyVoucher()">Apply Voucher</button>
            </div>

            <script>
function applyVoucher() {
    const voucherCode = prompt("Enter your voucher code:");
    if (voucherCode) {
        alert("You have won a Movie Voucher Check Your Mail to claim");
        // Here you can add further logic to validate the voucher code
    } else {
        alert("No voucher code entered.");
    }
}
</script>

         <input type="hidden" name="total_products" value="<?= $total_products; ?>">
         <input type="hidden" name="total_price" value="<?= $grand_total; ?>">
         <input type="hidden" name="name" value="<?= $fetch_profile['name'] ?>">
         <input type="hidden" name="number" value="<?= $fetch_profile['number'] ?>">
         <input type="hidden" name="email" value="<?= $fetch_profile['email'] ?>">
         <input type="hidden" name="address" value="<?= $fetch_profile['address'] ?>">

         <div class="user-info">
            <h3>Your Info</h3>
            <p><i class="fas fa-user"></i><span><?= $fetch_profile['name'] ?></span></p>
            <p><i class="fas fa-phone"></i><span><?= $fetch_profile['number'] ?></span></p>
            <p><i class="fas fa-envelope"></i><span><?= $fetch_profile['email'] ?></span></p>
            <a href="update_profile.php" class="btn">Update Info</a>
            <h3>Delivery Address</h3>
            <p><i class="fas fa-map-marker-alt"></i><span><?php if ($fetch_profile['address'] == '') {
                                                               echo 'Please enter your address';
                                                            } else {
                                                               echo $fetch_profile['address'];
                                                            } ?></span></p>
            <a href="update_address.php" class="btn">Update Address</a>
            <select name="method" class="box" required>
               <option value="" disabled selected>Select Payment Method --</option>
               <option value="cash on delivery">Cash on Delivery</option>
            </select>
            <input type="submit" value="Place Order" class="btn <?php if ($fetch_profile['address'] == '') 
                                                                ?>" style="width:100%; background:var(--red); color:var(--white);" name="submit">
         </div>



      </form>
      <?php if (isset($voucher_code) && !empty($voucher_code)) { ?>
         <div class="voucher">
            <h3>Congratulations! You have earned a voucher code: <span><?= $voucher_code; ?></span></h3>
         </div>
      <?php } ?>

   </section>

   <!-- footer section starts  -->
   <?php include 'components/footer.php'; ?>
   <!-- footer section ends -->

   <!-- custom js file link  -->
   <script src="js/script.js"></script>

</body>

</html>
