<?php
/**
 * Header
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
<style>
.checkout-section{
	padding: 5%; !important;
}
/* Parent menu item */
.nav-menu li {
  position: relative;
  list-style: none;
}

/* Submenu (hidden by default) */
.nav-menu .sub-menu {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  background: #fff;
  padding: 10px 0;
  min-width: 200px;
  z-index: 999;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* Submenu links */
.nav-menu .sub-menu li a {
  padding: 8px 15px;
  display: block;
  color: #333;
}

/* Show submenu on hover (desktop) */
.nav-menu li:hover > .sub-menu {
  display: block;
}
/* Add ▼ icon to parent menu items */
.nav-menu li.menu-item-has-children > a::after {
  content: " ▼"; /* you can use "▶" or Font Awesome icons */
  font-size: 12px;
  margin-left: 5px;
  transition: transform 0.2s ease;
}

/* Rotate arrow when hovering (desktop) */
.nav-menu li.menu-item-has-children:hover > a::after {
  transform: rotate(180deg);
}
/* ===== Header ===== */
.site-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: #fff;
  border-bottom: 1px solid #ddd;
  position: relative;
}

.site-logo img {
  height: 50px;
}

/* ===== Menu ===== */
.main-menu {
  display: flex;
  gap: 20px;
}

.main-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
}

.main-menu ul li {
  margin: 0 10px;
}

.main-menu ul li a {
  text-decoration: none;
  color: #0C1D21;
  font-weight: 500;
}

/* ===== Icons ===== */
.header-icons {
  display: flex;
  gap: 15px;
}

/* ===== Mobile Styles ===== */
.mobile-menu-btn {
  display: none;
  font-size: 26px;
  background: none;
  border: none;
  cursor: pointer;
}

.close-menu {
  display: none;
}
/* 🔹 Hide header icons on mobile & tablet */

@media (max-width: 500px) {
	.site-logo img{
		width:150px;
		height:auto;
	}
}
/* Mobile Sidebar */
@media (max-width: 1024px) {
  .main-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 280px;
    height: 100%;
    background: #4D009A;
    flex-direction: column;
    padding: 20px;
    transition: right 0.3s ease-in-out;
    z-index: 999;
  }

  .main-menu.active {
    right: 0;
  }

  .main-menu ul {
    flex-direction: column;
  }

  .main-menu ul li a {
    color: #fff;
    padding: 12px 0;
    display: block;
  }

  .mobile-menu-btn {
    display: block;
  }

  .close-menu {
    display: block;
    font-size: 30px;
    color: #fff;
    background: none;
    border: none;
    cursor: pointer;
    margin-bottom: 20px;
    align-self: flex-end;
  }
}
/* ===== Top Bar ===== */
  /* 🔹 Top Bar */
  .top-bar {
    background:#4D009A;
    padding:10px 0;
    overflow:hidden;
    position:relative;
  }
  .top-bar-slider {
    display:flex;
    gap:20px;
    white-space:nowrap;
    animation: marquee 20s linear infinite;
  }
  .top-bar-slider h6 {
  
    color:#fff;
    font-size:14px;
    margin:0;
  
   
    flex-shrink:0;
  }
  .top-bar-slider {
    display:flex;
    gap:15px;
    white-space:nowrap;
    animation: marquee 40s linear infinite;
  }

  .top-bar-slider:hover {
    animation-play-state: paused;
  }
  @keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
  }


/* ===== Header ===== */
.site-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: #fff;
  border-bottom: 1px solid #ddd;
  position: relative;
}

.site-logo img {
  height: 50px;
}

/* ===== Menu ===== */
.main-menu {
  display: flex;
  gap: 20px;
}

.main-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
}

.main-menu ul li {
  margin: 0 10px;
}

.main-menu ul li a {
  text-decoration: none;
  color: #0C1D21;
  font-weight: 500;
}

/* ===== Icons ===== */
.header-icons {
  display: flex;
  gap: 15px;
}

/* ===== Mobile Styles ===== */
.mobile-menu-btn {
  display: none;
  font-size: 26px;
  background: none;
  border: none;
  cursor: pointer;
}

.close-menu {
  display: none;
}
/* 🔹 Hide header icons on mobile & tablet */

@media (max-width: 500px) {
	.site-logo img{
		width:150px;
		height:auto;
	}
}
/* Mobile Sidebar */
@media (max-width: 1024px) {
  .main-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 280px;
    height: 100%;
    background: #4D009A;
    flex-direction: column;
    padding: 20px;
    transition: right 0.3s ease-in-out;
    z-index: 999;
  }

  .main-menu.active {
    right: 0;
  }

  .main-menu ul {
    flex-direction: column;
  }

  .main-menu ul li a {
    color: #fff;
    padding: 12px 0;
    display: block;
  }

  .mobile-menu-btn {
    display: block;
  }

  .close-menu {
    display: block;
    font-size: 30px;
    color: #fff;
    background: none;
    border: none;
    cursor: pointer;
    margin-bottom: 20px;
    align-self: flex-end;
  }
}

footer .menu-item  a{
color:white;
}
.site-footer{background:#0f0f0f;color:#fff;padding:2rem 1rem;margin-top:3rem}
.site-footer a{color:#ddd;text-decoration:none}
.footer-widgets{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem}
.copyright{text-align:center;font-size:13px;opacity:.8;margin-top:1.5rem}


/* 🔹 Search */

.header-search {
  display:none;
  padding: 5px 10px;
  background:#f9f9f9;
  border-top:1px solid #ddd;
  position:absolute;   /* make it float inside header */
  right:0;             /* stick to right side */
  width:25%;           /* only 25% width */
  z-index:9999;
  box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

.header-search .woocommerce-Price-amount {
    color: #444;
    font-weight: 600;
    font-size: 12px;
}

.header-icons a{
  color:#4D009A !important;
  display:flex;
  align-items:center;
}

.header-search input{
  width: 100%;
    border: 1px solid #eeeeee;
    border-radius: 20px;
    padding: 10px 20px;
}
.search-results-dropdown {
  display:none;
  background:#fff;
  border:1px solid #ddd;
  position:absolute;
  top:100%;
  left:0;
  width:100%;
  z-index:9999;
  max-height:300px;
  overflow-y:auto;
  border-radius:5px;
}

.search-results-dropdown ul {
  list-style:none;
  margin:0;
  padding:0;
}
.search-results-dropdown li {
  border-bottom:1px solid #eee;
}
.search-results-dropdown li a {
  display:flex;
  align-items:center;
  gap:10px;
  padding:8px;
  text-decoration:none;
  color:#333;
  font-size: 13px;
}
.search-results-dropdown img {
  width:40px;
  height:auto;
}
/* 🔹 Account */
.account-dropdown {
  position:relative;
  display:inline-block;
}
.account-dropdown .dropdown-menu {
  display:none;
  position:absolute;
  top:10px;
  right:0;
  background:#fff;
  border:1px solid #ddd;
  list-style:none;
  padding:10px;
  min-width:180px;
  z-index:9999;
  border-radius:5px;
}
.account-dropdown:hover .dropdown-menu {
  display:block;
}
.account-dropdown .dropdown-menu li {
  margin:5px 0;
}
/* 🔹 Cart */
.cart-icon {
  position:relative;
  display:inline-block;
}
.cart-count {
  position: absolute;
    top: -8px;
    right: -8px;
    min-width: 18px;
    width: auto;
    height: 18px;
    display: flex;    
    align-items: center;
    justify-content: center;
    background-color: #cf2e2e;
    border-radius: 100%;
    color: white;
    z-index: 20;
    font-weight: 600;
    font-size: 12px;
    line-height: 10px;
    box-sizing: border-box;
    padding: 2px;
}
.fkcart-item-misc .fkcart-item-price span{
  font-size:13px;
}




</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- 🔹 Top Bar -->
<div class="top-bar">
  <div class="top-bar-slider">
    <h6><?php echo esc_html__('Meet quality standards','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('FREE Shipping on domestic orders over 250+','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('USA-Domestic Delivery and same-day shipping','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('Meet quality standards','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('FREE Shipping on domestic orders over 250+','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('USA-Domestic Delivery and same-day shipping','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('Meet quality standards','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('FREE Shipping on domestic orders over 250+','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('USA-Domestic Delivery and same-day shipping','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('Meet quality standards','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('FREE Shipping on domestic orders over 250+','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('USA-Domestic Delivery and same-day shipping','amino-usa'); ?></h6>
    <h6><?php echo esc_html__('/','amino-usa'); ?></h6>
  </div>
</div>

<!-- 🔹 Header -->
<header class="site-header">
   <!-- Mobile Menu Toggle -->
   <button class="mobile-menu-btn" id="mobileMenuButton" aria-label="Toggle menu">☰</button>
  <!-- Logo -->
  <div class="site-logo">
    <a href="<?php echo esc_url( home_url('/') ); ?>">
      <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2024/01/Purple-Logo-Transparent-Background-1.png" alt="<?php bloginfo('name'); ?>">
    </a>
  </div>

 

  <!-- Main Menu -->
  <nav class="main-menu" id="mainMenu">
    <button class="close-menu" id="closeMenu">×</button>
    <?php
    wp_nav_menu( array(
        'theme_location' => 'primary',
        'menu_class'     => 'nav-menu',
        'container'      => false,
        'depth'          => 3,
        'fallback_cb'    => false,
    ) );
    ?>
  </nav>

  <!-- Icons -->
  <div class="header-icons">
    <!-- Search -->
    <a href="#" class="search-toggle"><span class="dashicons dashicons-search"></span></a>

    <!-- Account -->
    <?php if ( is_user_logged_in() ) : ?>
      <div class="account-dropdown">
        <a href="<?php echo esc_url( wc_get_account_endpoint_url('dashboard') ); ?>">
          <span class="dashicons dashicons-admin-users"></span>
        </a>
        <ul class="dropdown-menu">
          <li><a href="<?php echo esc_url( wc_get_account_endpoint_url('dashboard') ); ?>">Dashboard</a></li>
          <li><a href="<?php echo esc_url( wc_get_account_endpoint_url('orders') ); ?>">Orders</a></li>
          <li><a href="<?php echo esc_url( wc_get_account_endpoint_url('edit-account') ); ?>">Account Details</a></li>
          <li><a href="<?php echo esc_url( wc_get_account_endpoint_url('customer-logout') ); ?>">Logout</a></li>
        </ul>
      </div>
    <?php else : ?>
      <a href="<?php echo esc_url( get_permalink( get_option('woocommerce_myaccount_page_id') ) ); ?>">
        <span class="dashicons dashicons-admin-users"></span>
      </a>
    <?php endif; ?>

    <!-- Cart -->

    <a href="#" class="cart-icon" >
    <?php echo do_shortcode('[fk_cart_menu]'); ?>
    </a>

  </div>
</header>

<!-- 🔹 Search Field -->
<div class="header-search" id="headerSearch">
  <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="search" id="searchInput" class="search-field" 
           placeholder="Search products..." 
           value="<?php echo get_search_query(); ?>" 
           name="s" />
    <input type="hidden" name="post_type" value="product">
  </form>
  <div id="searchResults" class="search-results-dropdown"></div>
</div>






<main id="content" class="site-content">

<script>


jQuery(document).ready(function($) {
   // Toggle search bar
   $(".search-toggle").on("click", function(e) {
        e.preventDefault();
        $("#headerSearch").slideToggle();
        $("#searchInput").focus();
    });

    // Live product search
    $("#searchInput").on("keyup", function() {
        var term = $(this).val();
        if (term.length > 2) {
            $.get("<?php echo admin_url('admin-ajax.php'); ?>", {
                action: "product_search",
                term: term
            }, function(data) {
                $("#searchResults").html(data).show();
            });
        } else {
            $("#searchResults").hide();
        }
    });
  

});  
</script>




