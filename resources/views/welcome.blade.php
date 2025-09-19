<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SafeGuardX - Secure Your Data</title>
  @vite('resources/css/app.css')

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="font-inter antialiased px-4 md:px-0. px-4 bg-gray-900 text-white">

  <!-- Navbar Section -->
  <nav class="bg-gray-900 text-white py-4">
    <div class="container mx-auto flex justify-between items-center px-4 md:px-0">
      <!-- Logo + Brand -->
      <div class="flex items-center space-x-2">
<img src="{{ asset('storage/logo.png') }}" alt="SafeGuardX Logo" class="h-20 w-20 object-contain">
        <span class="text-8xl font-extrabold text-green-400">SafeGuardX</span>
      </div>

      <!-- Nav Links -->
      <div class="flex items-center space-x-4">
        <a href="login" class="text-gray-200 hover:text-white text-sm font-medium mr-2">Log in</a>
        <a href="register" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-none">Sign up</a>    
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
<header class="pt-16 pb-4 bg-cover bg-center relative" style="background-image: url('{{ asset('storage/image.png') }}');"
   
    <div class="absolute inset-0 bg-black/70"></div>

    <div class="container relative z-10 mx-auto flex flex-col md:flex-row items-center justify-between px-4 md:px-0">
      <!-- Left Content -->
      <div class="md:w-1/2 text-left mb-12 md:mb-0">
        <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
          Secure Your <span class="block">Data.</span>
          <span class="block text-yellow-400">Launch It<br/>Beyond Threats.</span>
        </h1>
        <p class="text-gray-50 text-lg mb-8 max-w-xl">
          Protecting your digital assets with cutting-edge technology. Our ransomware-proof backup system ensures your data stays safe, even when everything else fails.
        </p>
      </div>

      <!-- Right Feature Cards -->
      <div class="md:w-1/2 flex justify-center md:justify-end">
        <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-lg border border-gray-700">
          <div class="grid grid-cols-2 gap-6">
            <div class="flex flex-col items-center p-4 bg-gray-900 rounded-lg text-center border border-gray-800">
              <span class="text-green-400 text-2xl mb-2"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg></span>
              <p class="font-bold text-base">99.9% Uptime</p>
            </div>
            <div class="flex flex-col items-center p-4 bg-gray-900 rounded-lg text-center border border-gray-800">
              <span class="text-green-400 text-2xl mb-2"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 17v.01"/><rect x="7" y="11" width="10" height="6" rx="2"/><path d="M17 11V7a5 5 0 0 0-10 0v4"/></svg></span>
              <p class="font-bold text-base">Zero-Trust Security</p>
            </div>
            <div class="flex flex-col items-center p-4 bg-gray-900 rounded-lg text-center border border-gray-800">
              <span class="text-green-400 text-2xl mb-2"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7v4a9 9 0 0 0 18 0V7"/><path d="M21 7a9 9 0 0 0-18 0"/></svg></span>
              <p class="font-bold text-base">Multi-Cloud Backup</p>
            </div>
            <div class="flex flex-col items-center p-4 bg-gray-900 rounded-lg text-center border border-gray-800">
              <span class="text-green-400 text-2xl mb-2"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span>
              <p class="font-bold text-base">Instant Recovery</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Ransomware-Proof Protection Section -->
  <section class="bg-gray-900 py-16 md:py-24">
    <div class="container mx-auto px-4 md:px-0 text-center">
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">Ransomware-Proof Protection</h2>
      <p class="text-gray-300 text-lg md:text-xl mb-12 max-w-4xl mx-auto leading-relaxed">
        Our advanced backup system is designed to withstand even the most sophisticated attacks, ensuring your data remains safe and recoverable.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
        <!-- Card 1 -->
        <div class="bg-gray-800 p-8 rounded-xl shadow-xl flex flex-col items-center text-center border border-gray-700">
          <div class="text-green-400 text-6xl mb-4">&#128274;</div>
          <h3 class="text-2xl font-semibold mb-3">Immutable Backups</h3>
          <p class="text-gray-300 leading-relaxed">
            Once data is written, it cannot be modified or deleted by ransomware attacks, ensuring data integrity.
          </p>
        </div>

        <!-- Card 2 -->
        <div class="bg-gray-800 p-8 rounded-xl shadow-xl flex flex-col items-center text-center border border-gray-700">
          <div class="text-green-400 text-6xl mb-4">&#128272;</div>
          <h3 class="text-2xl font-semibold mb-3">End-to-End Encryption</h3>
          <p class="text-gray-300 leading-relaxed">
            Your data is encrypted in transit and at rest with zero-knowledge architecture for maximum privacy.
          </p>
        </div>

        <!-- Card 3 -->
        <div class="bg-gray-800 p-8 rounded-xl shadow-xl flex flex-col items-center text-center border border-gray-700">
          <div class="text-green-400 text-6xl mb-4">&#9889;</div>
          <h3 class="text-2xl font-semibold mb-3">Instant Recovery</h3>
          <p class="text-gray-300 leading-relaxed">
            Restore your data quickly and efficiently, minimizing downtime and business impact in any scenario.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose SafeGuardX Section -->
  <section class="bg-gray-900 py-16 md:py-24">
    <div class="container mx-auto flex flex-col md:flex-row items-center md:items-start justify-between px-4 md:px-0">
      <!-- Left -->
      <div class="md:w-1/2 mb-12 md:mb-0 md:pr-8">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-8">Why Choose SafeGuardX?</h2>
        <ul class="space-y-4 text-lg text-gray-300">
          <li class="flex items-start">
            <span class="text-green-400 mr-3 text-2xl mt-1">&#10003;</span>
            <span>100% ransomware-proof backup architecture</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-400 mr-3 text-2xl mt-1">&#10003;</span>
            <span>Automated daily backups with granular retention control</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-400 mr-3 text-2xl mt-1">&#10003;</span>
            <span>Cross-platform support: Windows, Linux, and Cloud environments</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-400 mr-3 text-2xl mt-1">&#10003;</span>
            <span>24/7 proactive monitoring and advanced threat detection</span>
          </li>
        </ul>
      </div>

      <!-- Right -->
      <div class="md:w-1/2 flex justify-center md:justify-end">
        <div class="bg-gray-800 p-8 rounded-xl shadow-2xl text-center w-full max-w-md border border-gray-700">
          <p class="text-gray-300 text-xl mb-8">Start to use today</p>
          <div class="grid grid-cols-2 gap-y-4 text-gray-300 text-base mb-10">
            <div class="text-left font-semibold">Storage</div>
            <div class="text-right">Up to 1TB</div>
            <div class="text-left font-semibold">Devices</div>
            <div class="text-right">Unlimited</div>
            <div class="text-left font-semibold">Support</div>
            <div class="text-right">24/7 Priority</div>
          </div>
          <button class="bg-green-500 hover:bg-green-600 text-white px-8 py-4 rounded-lg text-lg font-semibold w-full shadow-lg transition duration-300 ease-in-out">
            Secure. Backup. Recover.
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Call to Action Section -->
   <section class="bg-green-700 py-16 md:py-24 text-center" class="absolute inset-0 opacity-80" class="pt-16 pb-4 bg-cover bg-center relative" style="background-image: url('{{ asset('storage/foot-background.jpg') }}');">
    <div class="container mx-auto px-4 md:px-0">
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6">Ready to Secure Your Digital Future?</h2>
      <p class="text-green-100 text-lg md:text-xl mb-10 max-w-4xl mx-auto leading-relaxed">
        Join thousands of businesses who trust SafeGuardX to protect their most valuable data
      </p>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="bg-gray-900 py-12 text-white">
    <div class="container mx-auto px-4 md:px-0 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
      <!-- Info -->
      <div>
        <h3 class="text-xl font-bold mb-4 text-green-400">SafeGuardX</h3>
        <p class="text-gray-400 text-sm leading-relaxed">
          Protecting your digital assets with cutting-edge technology for peace of mind.
        </p>
      </div>

      <!-- Product Links -->
      <div>
        <h3 class="text-lg font-semibold mb-4">Product</h3>
        <ul class="space-y-2 text-gray-400 text-sm">
          <li><a href="#" class="hover:text-white transition duration-200">Features</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Solutions</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Integrations</a></li>
        </ul>
      </div>

      <!-- Company Links -->
      <div>
        <h3 class="text-lg font-semibold mb-4">Company</h3>
        <ul class="space-y-2 text-gray-400 text-sm">
          <li><a href="#" class="hover:text-white transition duration-200">About Us</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Careers</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Blog</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Contact</a></li>
        </ul>
      </div>

      <!-- Support Links -->
      <div>
        <h3 class="text-lg font-semibold mb-4">Support</h3>
        <ul class="space-y-2 text-gray-400 text-sm">
          <li><a href="#" class="hover:text-white transition duration-200">Help Center</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Documentation</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">API</a></li>
          <li><a href="#" class="hover:text-white transition duration-200">Community</a></li>
        </ul>
      </div>
    </div>
    <div class="container mx-auto text-center text-gray-500 text-xs mt-12 border-t border-gray-700 pt-8">
      <p>&copy; 2025 SafeGuardX. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>
