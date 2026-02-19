<template>
  <div class="bg-gradient-to-br from-pink-600 to-pink-700 min-h-screen flex items-center justify-center p-4">
    <div class="animate-fade-in max-w-md w-full">
      <div class="bg-white rounded-lg shadow-2xl p-8 text-center">
        <!-- Error Code -->
        <div class="animate-slide-in-down mb-6">
          <div class="error-code font-bold text-pink-600 mb-2">429</div>
        </div>

        <!-- Hourglass Icon -->
        <div class="mb-6 flex justify-center">
          <i class="fas fa-hourglass text-pink-500 opacity-80 animate-bounce-custom" style="font-size: 5rem;"></i>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-900 mb-3 animate-slide-in-down" style="animation-delay: 0.1s;">
          Too Many Requests
        </h1>

        <!-- Description -->
        <p class="text-gray-600 mb-8 leading-relaxed animate-fade-in" style="animation-delay: 0.2s;">
          You're sending requests too quickly. Please wait a moment before trying again.
        </p>

        <!-- Progress Bar -->
        <div class="mb-6 animate-fade-in" style="animation-delay: 0.25s;">
          <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
            <div class="progress-bar bg-gradient-to-r from-pink-600 to-pink-500 h-full"></div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3 animate-fade-in" style="animation-delay: 0.3s;">
          <button @click="location.reload()" class="block w-full bg-gradient-to-r from-pink-600 to-pink-700 text-white font-semibold py-3 rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-300">
            Try Again
          </button>
          <Link href="/" class="block w-full border-2 border-pink-600 text-pink-600 font-semibold py-3 rounded-lg hover:bg-pink-50 transition-colors duration-300">
            Go to Home
          </Link>
        </div>

        <!-- Decorative Element -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            Error Code: <span class="font-mono font-semibold text-gray-700">429</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes bounce-custom {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

@keyframes progress-bar {
  0% { width: 0%; }
  100% { width: 100%; }
}

.animate-slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

.animate-bounce-custom {
  animation: bounce-custom 1.5s ease-in-out infinite;
}

.progress-bar {
  animation: progress-bar 3s ease-in-out;
}

.error-code {
  font-size: 5rem;
  line-height: 1;
}
</style>
