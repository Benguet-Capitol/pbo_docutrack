<template>
  <div class="bg-gradient-to-br from-red-500 to-rose-600 min-h-screen flex items-center justify-center p-4">
    <div class="animate-fade-in max-w-md w-full">
      <div class="bg-white rounded-lg shadow-2xl p-8 text-center">
        <!-- Error Code -->
        <div class="animate-slide-in-down mb-6">
          <div class="error-code font-bold text-red-600 mb-2 animate-shake">500</div>
        </div>

        <!-- Alert Icon -->
        <div class="mb-6 flex justify-center">
          <i class="fas fa-exclamation-triangle text-red-500 opacity-80 animate-shake" style="font-size: 5rem;"></i>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-900 mb-3 animate-slide-in-down" style="animation-delay: 0.1s;">
          Server Error
        </h1>

        <!-- Description -->
        <p class="text-gray-600 mb-2 leading-relaxed animate-fade-in" style="animation-delay: 0.2s;">
          Something went wrong on our end. Our team has been notified and is working to fix the issue.
        </p>
        <p class="text-gray-500 text-sm mb-8 animate-fade-in" style="animation-delay: 0.25s;">
          Please try again later or contact support if the problem persists.
        </p>

        <!-- Action Buttons -->
        <div class="space-y-3 animate-fade-in" style="animation-delay: 0.3s;">
          <button @click="location.reload()" class="block w-full bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold py-3 rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-300">
            Try Again
          </button>
          <Link href="/" class="block w-full border-2 border-red-600 text-red-600 font-semibold py-3 rounded-lg hover:bg-red-50 transition-colors duration-300">
            Go to Home
          </Link>
        </div>

        <!-- Decorative Element -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            Error Code: <span class="font-mono font-semibold text-gray-700">500</span>
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

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.animate-slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}

.error-code {
  font-size: 5rem;
  line-height: 1;
}
</style>
