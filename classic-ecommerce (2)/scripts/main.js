// Home page functionality
const products = [] // Assuming products array is now imported from data.js

let currentSlide = 0
let slideInterval

document.addEventListener("DOMContentLoaded", () => {
  loadFeaturedProducts()
  initSlider()
})

function loadFeaturedProducts() {
  const container = document.getElementById("featuredProducts")
  if (!container) return

  // Show first 6 products as featured
  const featured = products.slice(0, 6)

  container.innerHTML = featured
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

function initSlider() {
  const slides = document.querySelectorAll(".slide")
  const dotsContainer = document.getElementById("sliderDots")
  const prevBtn = document.getElementById("prevBtn")
  const nextBtn = document.getElementById("nextBtn")

  if (!slides.length || !dotsContainer) return

  // Create dots
  slides.forEach((_, index) => {
    const dot = document.createElement("div")
    dot.classList.add("dot")
    if (index === 0) dot.classList.add("active")
    dot.addEventListener("click", () => goToSlide(index))
    dotsContainer.appendChild(dot)
  })

  // Navigation buttons
  if (prevBtn) prevBtn.addEventListener("click", prevSlide)
  if (nextBtn) nextBtn.addEventListener("click", nextSlide)

  // Auto-play
  startAutoPlay()

  // Pause on hover
  const sliderContainer = document.querySelector(".hero-slider")
  if (sliderContainer) {
    sliderContainer.addEventListener("mouseenter", stopAutoPlay)
    sliderContainer.addEventListener("mouseleave", startAutoPlay)
  }
}

function goToSlide(index) {
  const slides = document.querySelectorAll(".slide")
  const dots = document.querySelectorAll(".dot")

  slides[currentSlide].classList.remove("active")
  dots[currentSlide].classList.remove("active")

  currentSlide = index

  slides[currentSlide].classList.add("active")
  dots[currentSlide].classList.add("active")
}

function nextSlide() {
  const slides = document.querySelectorAll(".slide")
  const nextIndex = (currentSlide + 1) % slides.length
  goToSlide(nextIndex)
}

function prevSlide() {
  const slides = document.querySelectorAll(".slide")
  const prevIndex = (currentSlide - 1 + slides.length) % slides.length
  goToSlide(prevIndex)
}

function startAutoPlay() {
  slideInterval = setInterval(nextSlide, 5000) // Change slide every 5 seconds
}

function stopAutoPlay() {
  clearInterval(slideInterval)
}
