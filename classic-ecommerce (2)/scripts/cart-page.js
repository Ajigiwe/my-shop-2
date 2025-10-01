// Cart page functionality
const cart = {
  items: [],
  updateQuantity: function (productId, newQuantity) {
    const item = this.items.find((item) => item.id === productId)
    if (item) {
      item.quantity = newQuantity
    }
  },
  removeItem: function (productId) {
    this.items = this.items.filter((item) => item.id !== productId)
  },
  getTotal: function () {
    return this.items.reduce((total, item) => total + item.price * item.quantity, 0)
  },
}

document.addEventListener("DOMContentLoaded", () => {
  displayCart()
})

function displayCart() {
  const cartLayout = document.getElementById("cartLayout")
  const emptyCart = document.getElementById("emptyCart")
  const cartItemsContainer = document.getElementById("cartItems")

  if (cart.items.length === 0) {
    cartLayout.style.display = "none"
    emptyCart.style.display = "block"
    return
  }

  cartLayout.style.display = "grid"
  emptyCart.style.display = "none"

  // Display cart items
  cartItemsContainer.innerHTML = cart.items
    .map(
      (item) => `
    <div class="cart-item">
      <img src="${item.image}" alt="${item.name}" class="cart-item-image">
      <div class="cart-item-info">
        <h3 class="cart-item-name">${item.name}</h3>
        <p class="cart-item-category">${item.category}</p>
        <p class="cart-item-price">$${item.price.toFixed(2)}</p>
      </div>
      <div class="cart-item-actions">
        <div class="cart-item-quantity">
          <button class="cart-qty-btn" onclick="updateItemQuantity(${item.id}, ${item.quantity - 1})">−</button>
          <span class="cart-qty-display">${item.quantity}</span>
          <button class="cart-qty-btn" onclick="updateItemQuantity(${item.id}, ${item.quantity + 1})">+</button>
        </div>
        <button class="remove-btn" onclick="removeItem(${item.id})">Remove</button>
      </div>
    </div>
  `,
    )
    .join("")

  updateCartSummary()
}

function updateItemQuantity(productId, newQuantity) {
  if (newQuantity < 1) return
  cart.updateQuantity(productId, newQuantity)
  displayCart()
}

function removeItem(productId) {
  cart.removeItem(productId)
  displayCart()
}

function updateCartSummary() {
  const subtotal = cart.getTotal()
  const shipping = subtotal > 0 ? (subtotal > 500 ? 0 : 50) : 0
  const tax = subtotal * 0.08
  const total = subtotal + shipping + tax

  document.getElementById("subtotal").textContent = `$${subtotal.toFixed(2)}`
  document.getElementById("shipping").textContent = shipping === 0 && subtotal > 0 ? "FREE" : `$${shipping.toFixed(2)}`
  document.getElementById("tax").textContent = `$${tax.toFixed(2)}`
  document.getElementById("total").textContent = `$${total.toFixed(2)}`
}
