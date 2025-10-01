// Product detail page functionality
const products = [] // Declare the products variable here
const cart = { addItem: () => {} } // Declare the cart variable here

document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search)
  const productId = Number.parseInt(urlParams.get("id"))

  if (productId) {
    loadProductDetail(productId)
    loadRelatedProducts(productId)
  }
})

function loadProductDetail(productId) {
  const product = products.find((p) => p.id === productId)
  if (!product) return

  // Update breadcrumb
  document.getElementById("breadcrumbProduct").textContent = product.name

  // Update page title
  document.title = `${product.name} - Atelier`

  const container = document.getElementById("productDetail")
  container.innerHTML = `
    <div class="product-images">
      <img src="${product.image}" alt="${product.name}" class="main-image" id="mainImage">
      <div class="thumbnail-images">
        <img src="${product.image}" alt="${product.name}" class="thumbnail active" onclick="changeImage('${product.image}', this)">
        <img src="${product.image}" alt="${product.name}" class="thumbnail" onclick="changeImage('${product.image}', this)">
        <img src="${product.image}" alt="${product.name}" class="thumbnail" onclick="changeImage('${product.image}', this)">
        <img src="${product.image}" alt="${product.name}" class="thumbnail" onclick="changeImage('${product.image}', this)">
      </div>
    </div>
    <div class="product-info-section">
      <p class="product-category-detail">${product.category}</p>
      <h1 class="product-title">${product.name}</h1>
      <p class="product-price-detail">$${product.price.toFixed(2)}</p>
      <p class="product-description">${product.description}</p>
      
      <div class="product-details">
        <h3>Product Details</h3>
        <ul>
          ${product.details.map((detail) => `<li>${detail}</li>`).join("")}
        </ul>
      </div>
      
      <div class="quantity-selector">
        <label>Quantity:</label>
        <div class="quantity-controls">
          <button type="button" class="quantity-btn" onclick="decrementQuantity()">−</button>
          <input type="number" id="quantity" class="quantity-input" value="1" min="1">
          <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
        </div>
      </div>
      
      <button class="btn add-to-cart-btn" onclick="addToCart(${product.id})">Add to Cart</button>
    </div>
  `
}

function changeImage(src, thumbnail) {
  document.getElementById("mainImage").src = src
  document.querySelectorAll(".thumbnail").forEach((t) => t.classList.remove("active"))
  thumbnail.classList.add("active")
}

function incrementQuantity() {
  const input = document.getElementById("quantity")
  input.value = Number.parseInt(input.value) + 1
}

function decrementQuantity() {
  const input = document.getElementById("quantity")
  if (Number.parseInt(input.value) > 1) {
    input.value = Number.parseInt(input.value) - 1
  }
}

function addToCart(productId) {
  const product = products.find((p) => p.id === productId)
  const quantity = Number.parseInt(document.getElementById("quantity").value)

  cart.addItem(product, quantity)

  // Show feedback
  const btn = document.querySelector(".add-to-cart-btn")
  const originalText = btn.textContent
  btn.textContent = "Added to Cart!"
  btn.style.backgroundColor = "var(--color-accent)"

  setTimeout(() => {
    btn.textContent = originalText
    btn.style.backgroundColor = ""
  }, 2000)
}

function loadRelatedProducts(currentProductId) {
  const currentProduct = products.find((p) => p.id === currentProductId)
  const related = products
    .filter((p) => p.id !== currentProductId && p.category === currentProduct.category)
    .slice(0, 4)

  const container = document.getElementById("relatedProducts")
  container.innerHTML = related
    .map(
      (product) => `
    <div class="product-card" onclick="goToProduct(${product.id})">
      <img src="${product.image}" alt="${product.name}" class="product-image">
      <div class="product-info">
        <p class="product-category">${product.category}</p>
        <h3 class="product-name">${product.name}</h3>
        <p class="product-price">$${product.price.toFixed(2)}</p>
      </div>
    </div>
  `,
    )
    .join("")
}

function goToProduct(id) {
  window.location.href = `product-detail.html?id=${id}`
}
