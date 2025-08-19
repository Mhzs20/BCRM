<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات نوبت</title>
    <meta name="lat" content="{{ $appointment->salon->lat ?? 35.7219 }}">
    <meta name="lang" content="{{ $appointment->salon->lang ?? 51.3347 }}">
    <meta name="appointment-date" content="{{ \Carbon\Carbon::parse($appointment->start_time)->toIso8601String() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body class="bg-gray-100 font-peyda text-right">
@php
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
@endphp
<main class="w-full max-w-md mx-auto bg-gray-100 rounded-3xl p-4">
  <div class="flex flex-col items-center justify-center ">
      <header class="relative w-full  bg-emerald-50 rounded-bl-3xl rounded-br-3xl border-b-2 border-teal-900 text-center py-8">
          <div class="w-32 h-full z-10 mx-auto bg-zinc-300 rounded-full border-black overflow-hidden">
              <img class="w-32 h-full z-10 object-cover relative"
                   src="{{ $appointment->salon->image ?? 'https://placehold.co/134x134' }}"
                   alt="{{ $appointment->salon->name }}"/>
          </div>
          <div class="mt-4 z-10">
              <h1 class="text-neutral-700 text-xl font-black">{{ $appointment->salon->name }}</h1>
              <p class="text-teal-900 text-lg font-bold">{{ optional($appointment->customer)->name }} جان</p>
              <div class="inline-flex justify-center items-center gap-1.5">
                  <span class="text-teal-900 text-base font-bold">نوبت شما با موفقیت ثبت گردید</span>
                  <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <g clip-path="url(#clip0_4308_1321)">
                          <path
                              d="M20.1693 10.1567V11C20.1681 12.9768 19.5281 14.9002 18.3445 16.4834C17.1609 18.0666 15.4973 19.2248 13.6017 19.7853C11.7061 20.3457 9.68009 20.2784 7.82587 19.5934C5.97165 18.9084 4.38854 17.6423 3.31265 15.984C2.23676 14.3257 1.72575 12.3641 1.85581 10.3917C1.98587 8.41922 2.75004 6.54167 4.03436 5.03902C5.31868 3.53637 7.05432 2.48914 8.98244 2.05351C10.9106 1.61787 12.9278 1.81718 14.7334 2.62171"
                              stroke="#215242" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M20.1667 3.66602L11 12.8418L8.25 10.0918" stroke="#215242" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"/>
                      </g>
                      <defs>
                          <clipPath id="clip0_4308_1321">
                              <rect width="22" height="22" fill="white"/>
                          </clipPath>
                      </defs>
                  </svg>
              </div>
          </div>
      </header>
      <div class="absolute z-0">
          <svg width="360" height="120" viewBox="0 0 360 120" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M293.758 112.696L294.313 112.55L295.933 112.214C296.662 112.083 297.538 111.878 298.574 111.616C299.61 111.353 300.792 111.06 302.12 110.637C309.609 108.389 316.682 104.936 323.062 100.412C332.575 93.7128 340.265 84.7404 345.433 74.3101C351.769 61.5215 354.243 47.1621 352.555 32.988L352.978 33.3093H352.671C342.504 34.5744 332.775 38.2079 324.264 43.918C315.754 49.6281 308.699 57.2559 303.667 66.1889C300.987 70.8813 298.778 75.8279 297.071 80.9562C294.549 88.3958 293.238 96.193 293.189 104.049C293.164 105.282 293.203 106.516 293.306 107.745C293.333 108.649 293.411 109.551 293.54 110.447C293.54 111.148 293.671 111.689 293.729 112.098C293.745 112.287 293.745 112.478 293.729 112.667C293.679 112.485 293.64 112.299 293.613 112.112C293.613 111.703 293.467 111.163 293.35 110.462C293.198 109.567 293.096 108.665 293.043 107.759C292.941 106.693 292.854 105.452 292.854 104.064C292.793 96.1653 294.026 88.31 296.502 80.8101C298.183 75.6208 300.383 70.6146 303.069 65.8675C306.118 60.3809 309.942 55.3631 314.422 50.9687C324.78 40.8369 338.158 34.3648 352.525 32.5352H352.832H353.211V32.9149C354.909 47.2402 352.372 61.7494 345.915 74.6461C343.123 80.248 339.594 85.4504 335.422 90.1145C331.775 94.1517 327.688 97.7675 323.237 100.894C316.789 105.427 309.626 108.844 302.047 111.002C299.335 111.79 296.563 112.357 293.758 112.696Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M336.215 56.1211C336.215 56.1211 336.113 56.3402 335.835 56.7346L334.595 58.3851L329.954 64.3592C326.014 69.3839 320.512 76.3075 314.412 83.9175C308.313 91.5276 302.869 98.5242 299.206 103.695C297.353 106.295 295.908 108.442 294.931 109.932C294.453 110.732 293.922 111.498 293.34 112.225C293.418 111.993 293.526 111.772 293.661 111.568C293.88 111.159 294.215 110.545 294.682 109.771C295.587 108.238 296.959 106.047 298.754 103.388C302.329 98.0714 307.7 91.031 313.814 83.4355C319.929 75.84 325.489 68.9603 329.531 64.0233L334.361 58.1806L335.719 56.6177C335.863 56.4328 336.03 56.2661 336.215 56.1211Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M274.09 92.7705C274.08 92.6539 274.08 92.5366 274.09 92.42C274.09 92.1571 274.177 91.8357 274.25 91.4413C274.411 90.5649 274.6 89.2795 274.761 87.6144C275.195 82.9071 274.889 78.1609 273.856 73.5482C272.366 66.6935 269.202 60.3151 264.648 54.9832C259.063 48.448 251.661 43.7243 243.385 41.4136L243.852 41.1507V41.326C241.628 49.6595 242.092 58.4838 245.18 66.537C249.733 77.493 258.324 86.2749 269.172 91.0616C269.945 91.3975 270.631 91.6458 271.258 91.8503C271.765 92.0474 272.281 92.2181 272.805 92.3615L273.768 92.6537C273.987 92.6537 274.104 92.7559 274.09 92.7705C272.368 92.4685 270.685 91.9787 269.069 91.3099C264.626 89.5335 260.52 87.009 256.928 83.8459C251.473 79.2495 247.216 73.3945 244.524 66.7853C241.319 58.5994 240.808 49.6037 243.064 41.1069V40.9316L243.152 40.5664L243.531 40.6687C251.951 43.0121 259.471 47.8369 265.115 54.5157C269.713 59.9478 272.878 66.4457 274.323 73.4167C275.329 78.0845 275.556 82.887 274.994 87.629C274.853 88.9167 274.633 90.1947 274.338 91.4559C274.25 91.8503 274.162 92.1717 274.104 92.4346C274.121 92.5465 274.116 92.6605 274.09 92.7705Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M252.562 55.832C252.936 56.2761 253.25 56.7671 253.496 57.2927C254.109 58.3736 254.868 59.7028 255.773 61.2657C257.67 64.6398 260.253 69.314 263.07 74.4993C265.886 79.6847 268.455 84.3588 270.483 87.6453C271.49 89.2958 272.336 90.5666 272.905 91.516C273.25 91.9664 273.539 92.4569 273.766 92.9767C273.35 92.6001 272.983 92.1733 272.672 91.7059C272.015 90.8441 271.096 89.5587 270.031 87.952C267.871 84.7239 265.2 80.079 262.398 74.806C259.596 69.533 257.101 64.9028 255.321 61.4556C254.503 59.8489 253.861 58.5343 253.248 57.3803C252.961 56.8922 252.731 56.3727 252.562 55.832Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M289.326 57.7128C289.326 57.7128 290.085 57.0117 291.355 55.6387C293.151 53.7088 294.629 51.5048 295.733 49.1095C297.373 45.5761 298.124 41.6948 297.922 37.804C297.661 33.0612 296.034 28.4953 293.237 24.658L293.763 24.7456C289.952 27.6368 287.161 31.6686 285.795 36.2557C284.745 40.0142 284.595 43.9675 285.357 47.7949C285.822 50.3917 286.688 52.9 287.925 55.2297C288.83 56.8656 289.501 57.669 289.385 57.7128C289.187 57.5504 289.011 57.364 288.859 57.1577C288.397 56.5995 287.982 56.0034 287.619 55.3757C286.248 53.0652 285.285 50.5352 284.773 47.8971C283.914 43.9828 284.014 39.9184 285.065 36.0512C286.443 31.3043 289.308 27.1259 293.237 24.1321H293.325L293.631 23.8984L293.85 24.2052C296.755 28.1752 298.421 32.9171 298.637 37.8332C298.78 41.1659 298.232 44.4923 297.026 47.6022C295.821 50.7121 293.984 53.5381 291.632 55.9016C291.123 56.4217 290.582 56.9095 290.012 57.3622C289.56 57.5813 289.341 57.742 289.326 57.7128Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M292.43 34.0039C292.459 35.1802 292.356 36.3561 292.124 37.5095C291.846 39.6421 291.408 42.6072 290.869 45.8499C290.329 49.0926 289.876 52.0431 289.613 54.1903C289.538 55.3554 289.372 56.5128 289.117 57.6521C288.961 56.4839 288.961 55.3 289.117 54.1319C289.249 51.9701 289.628 48.9903 290.168 45.733C290.708 42.4758 291.233 39.5398 291.627 37.4219C291.774 36.2574 292.043 35.1117 292.43 34.0039Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M326.423 114.135C326.423 114.135 327.254 114.749 328.83 115.727C331.053 117.094 333.476 118.105 336.01 118.722C339.764 119.639 343.688 119.599 347.422 118.605C351.992 117.392 356.1 114.85 359.228 111.301V111.842H359.155C355.625 108.669 351.168 106.718 346.444 106.277C342.582 105.993 338.708 106.645 335.149 108.176C332.727 109.171 330.477 110.541 328.48 112.236C327.021 113.449 326.452 114.281 326.393 114.223C326.335 114.164 326.525 114.004 326.817 113.609C327.264 113.044 327.752 112.512 328.276 112.017C330.23 110.19 332.48 108.708 334.93 107.635C338.557 105.995 342.531 105.273 346.503 105.532C351.384 105.958 355.994 107.963 359.637 111.243H359.71L360.002 111.491L359.739 111.783C356.516 115.45 352.265 118.061 347.539 119.277C344.331 120.089 340.989 120.218 337.728 119.654C334.467 119.09 331.361 117.847 328.612 116.005C328.012 115.607 327.442 115.168 326.904 114.69C326.583 114.34 326.408 114.164 326.423 114.135Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M349.987 112.444C348.857 112.702 347.702 112.839 346.543 112.853C344.412 112.999 341.465 113.16 338.196 113.292C334.927 113.423 332.008 113.54 329.892 113.715C328.748 113.888 327.591 113.961 326.434 113.934C327.526 113.526 328.671 113.276 329.834 113.189C332.598 112.823 335.379 112.603 338.167 112.532C341.435 112.401 344.383 112.327 346.514 112.313C347.673 112.232 348.837 112.276 349.987 112.444Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M291.865 0.496626C290.722 0.251835 289.56 0.110069 288.392 0.0730326C285.019 0.165156 281.734 1.17507 278.891 2.99436C276.795 4.2417 274.838 5.71078 273.054 7.37635C271.113 9.20218 269.172 11.364 267.071 13.6572C264.861 16.1679 262.445 18.4887 259.847 20.5954C256.904 22.9409 253.473 24.5978 249.807 25.4448C245.736 26.2484 241.555 26.3128 237.461 25.6347C233.05 24.917 228.709 23.8173 224.488 22.3482C220.11 20.8875 215.659 19.1347 210.96 17.7617C206.167 16.2333 201.189 15.3636 196.162 15.1763C190.875 14.9618 185.651 16.4015 181.219 19.2954C176.71 22.2167 173.368 26.8032 170.303 31.4773C167.238 36.1514 164.466 41.2784 161.051 46.0839C160.234 47.2963 159.241 48.3918 158.307 49.5457C157.826 50.1154 157.271 50.612 156.761 51.1525C156.248 51.6795 155.697 52.1678 155.112 52.6131L153.36 54.0738C152.733 54.4974 152.062 54.8625 151.419 55.2569L150.456 55.8412L149.391 56.2794L147.275 57.1266C141.438 58.9816 135.119 57.9153 129.675 55.8266C124.373 53.5561 119.333 50.7155 114.644 47.3547C110.145 44.1481 105.328 41.4121 100.27 39.1896C95.3805 37.1868 90.2396 35.8648 84.991 35.2604C75.2374 34.1711 65.3639 35.1669 56.0234 38.1818C51.7946 39.5154 47.6885 41.2111 43.7505 43.2503C40.1289 45.1552 36.6426 47.3074 33.3163 49.6918C27.6021 53.8252 22.3302 58.539 17.5848 63.758C13.9753 67.6765 10.6217 71.8237 7.5447 76.1736C4.97629 79.7376 3.16671 82.6152 1.94088 84.5724C1.34256 85.5365 0.890168 86.2814 0.481557 86.8073C0.0729466 87.3331 0 87.5668 0 87.5668C0 87.5668 0.131329 87.2893 0.423194 86.7781L1.73658 84.4994C2.91863 82.5129 4.65524 79.5916 7.22365 75.9983C10.2759 71.6126 13.6051 67.4265 17.1908 63.4658C21.9275 58.1946 27.1997 53.4315 32.9223 49.2536C36.2673 46.8483 39.7676 44.6672 43.4003 42.7244C47.3664 40.6609 51.5017 38.9408 55.7608 37.5829C65.1667 34.5214 75.1153 33.4958 84.9472 34.5739C90.2636 35.1859 95.4723 36.5175 100.431 38.5323C105.534 40.7642 110.395 43.515 114.936 46.7412C119.587 50.0762 124.588 52.8925 129.851 55.1401C135.192 57.1996 141.292 58.2221 146.954 56.4254C152.455 54.3633 157.15 50.5903 160.351 45.6604C163.765 40.9716 166.48 35.8885 169.573 31.1414C172.667 26.3942 176.067 21.8077 180.723 18.7695C185.249 15.7775 190.596 14.2761 196.016 14.4752C201.104 14.6697 206.142 15.5592 210.989 17.119C215.717 18.5797 220.168 20.2886 224.532 21.7493C228.714 23.219 233.015 24.3235 237.388 25.0504C241.416 25.7325 245.533 25.6879 249.544 24.9189C253.167 24.1342 256.568 22.547 259.497 20.274C262.085 18.1932 264.496 15.9019 266.706 13.4235C268.822 11.1449 270.778 9.04151 272.748 7.17186C274.534 5.50927 276.49 4.04043 278.585 2.78987C281.478 0.978635 284.819 0.0123676 288.231 6.10669e-06C288.72 -0.000562076 289.208 0.0385195 289.69 0.116862C290.06 0.157415 290.426 0.225772 290.785 0.321354C291.573 0.394387 291.865 0.496626 291.865 0.496626Z" fill="#215242" fill-opacity="0.4"/>
              <path d="M137.773 27.9418C137.773 27.9418 137.875 27.9418 138.094 27.8249C138.411 27.7577 138.734 27.7234 139.057 27.7227C140.266 27.7604 141.438 28.1514 142.428 28.8474C143.179 29.3404 143.818 29.9848 144.306 30.7393C144.793 31.4939 145.118 32.342 145.259 33.2294C145.392 34.3177 145.182 35.4202 144.661 36.3844C144.08 37.3972 143.232 38.2308 142.209 38.7945C141.249 39.4073 140.114 39.689 138.979 39.5967C137.843 39.5045 136.769 39.0432 135.92 38.2833C135.302 37.6053 134.861 36.7857 134.634 35.8967C134.408 35.0078 134.403 34.0767 134.621 33.1855C134.86 31.9825 135.536 30.9106 136.518 30.1766C136.787 29.996 137.082 29.8581 137.394 29.7676C137.613 29.7676 137.715 29.6799 137.729 29.7676C137.744 29.8552 137.306 29.9721 136.693 30.4833C135.844 31.2401 135.295 32.2768 135.146 33.4046C134.988 34.2005 135.019 35.0224 135.237 35.804C135.455 36.5855 135.854 37.3048 136.401 37.9035C137.152 38.5359 138.086 38.9111 139.065 38.9742C140.045 39.0373 141.019 38.7849 141.845 38.254C142.738 37.7502 143.483 37.0197 144.004 36.1361C144.469 35.3112 144.663 34.3607 144.559 33.4192C144.451 32.6098 144.179 31.8309 143.76 31.1301C143.341 30.4294 142.784 29.8216 142.122 29.344C141.225 28.6589 140.162 28.2253 139.043 28.0878C138.24 27.9271 137.788 28.0148 137.773 27.9418Z" fill="#215242" fill-opacity="0.4"/>
          </svg>
      </div>
  </div>

    <section class="mt-5">
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-white rounded-lg shadow p-4">
                <div
                    class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                    <img src="{{ asset('assets/img/calender.png') }}" alt="calender" class="w-full h-full"/>
                </div>
                <p class="text-neutral-700 text-lg font-bold mt-2 font-iranyekan">{{ str_replace($englishDigits, $persianDigits, verta($appointment->start_time)->format('Y/m/d')) }}</p>
                <p class="text-neutral-400 text-xs font-bold">{{ verta($appointment->start_time)->format('l') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div
                    class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                    <img src="{{ asset('assets/img/time.png') }}" alt="time" class="w-full h-full"/>
                </div>
                <p id="appointment-time-display" class="text-neutral-700 text-lg font-bold mt-2 font-iranyekan"
                   data-time="{{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}">{{ str_replace($englishDigits, $persianDigits, \Carbon\Carbon::parse($appointment->start_time)->format('H:i')) }}</p>
                <p class="text-neutral-400 text-xs font-bold">{{ \Carbon\Carbon::parse($appointment->start_time)->format('H') < 12 ? 'قبل از ظهر' : 'بعد از ظهر' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div
                    class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                    <img src="{{ asset('assets/img/services.png') }}" alt="services" class="w-full h-full"/>
                </div>
                <p class="text-neutral-700 text-lg font-bold mt-2 font-iranyekan">
                    @foreach($appointment->services as $service)
                        {{ $service->name }}@if(!$loop->last)
                            ,
                        @endif
                    @endforeach
                </p>
                <p class="text-neutral-400 text-xs font-bold">خدمات سالن</p>
            </div>
        </div>
    </section>

    <section id="countdown" class="mt-5"></section>

    <div class="my-5 h-px bg-zinc-300"></div>

    <section class="mt-5">
        <div class="flex justify-end items-center gap-1.5">
            <div class="bg-orange-400/10 rounded-[10px] px-4 py-1">
                <span class="text-orange-400 text-sm font-bold font-iranyekan">مهــم</span>
            </div>
            <h3 class="text-neutral-700 text-sm font-bold">توضیحات نوبت</h3>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 8V12" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 16H12.01" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="mt-4 bg-white rounded-lg shadow p-4">
            <p dir="rtl" class="text-neutral-700 text-sm font-normal font-iranyekan leading-normal">{{ $appointment->notes }}</p>
        </div>
    </section>

    <section class="mt-5">
        <div class="flex justify-end items-center">
            <h3 class="text-neutral-700 text-sm mr-2 font-bold text-right">ارتباط با سالن</h3>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 13C10.4295 13.5741 10.9774 14.0491 11.6066 14.3929C12.2357 14.7367 12.9315 14.9411 13.6467 14.9923C14.3618 15.0435 15.0796 14.9403 15.7513 14.6897C16.4231 14.4392 17.0331 14.047 17.54 13.54L20.54 10.54C21.4508 9.59695 21.9548 8.33394 21.9434 7.02296C21.932 5.71198 21.4061 4.45791 20.4791 3.53087C19.5521 2.60383 18.298 2.07799 16.987 2.0666C15.676 2.0552 14.413 2.55918 13.47 3.46997L11.75 5.17997" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.9982 10.9992C13.5688 10.4251 13.0209 9.95007 12.3917 9.60631C11.7625 9.26255 11.0667 9.05813 10.3516 9.00691C9.63645 8.9557 8.91866 9.05888 8.2469 9.30947C7.57514 9.56005 6.96513 9.95218 6.45825 10.4592L3.45825 13.4592C2.54746 14.4023 2.04348 15.6653 2.05488 16.9763C2.06627 18.2872 2.59211 19.5413 3.51915 20.4683C4.44619 21.3954 5.70026 21.9212 7.01124 21.9326C8.32222 21.944 9.58524 21.44 10.5282 20.5292L12.2382 18.8192" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="grid grid-cols-3 gap-2 mt-3.5">
            @if($appointment->salon->whatsapp)
                <a href="https://wa.me/0098{{ $appointment->salon->whatsapp }}"
                   class="bg-white rounded-lg shadow p-4 text-center">
                    <div
                        class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                        <img src="{{ asset('assets/img/whatsapp.png') }}" alt="whatsapp" class="w-full h-full"/>
                    </div>
                    <p class="text-neutral-700 text-sm font-bold mt-2">واتس اپ</p>
                    <p class="text-neutral-400 text-[8px] font-light">{{ str_replace($englishDigits, $persianDigits, $appointment->salon->whatsapp) }}</p>
                </a>
            @endif
            @if($appointment->salon->telegram)
                <a href="https://t.me/{{ $appointment->salon->telegram }}"
                   class="bg-white rounded-lg shadow p-4 text-center">
                    <div
                        class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                        <img src="{{ asset('assets/img/telegram.png') }}" alt="telegram" class="w-full h-full"/>
                    </div>
                    <p class="text-neutral-700 text-sm font-bold mt-2">تلگرام</p>
                    <p class="text-neutral-400 text-[8px] font-light">{{ $appointment->salon->telegram }}</p>
                </a>
            @endif
            @if($appointment->salon->instagram)
                <a href="https://instagram.com/{{ $appointment->salon->instagram }}"
                   class="bg-white rounded-lg shadow p-4 text-center">
                    <div
                        class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                        <img src="{{ asset('assets/img/instagram.png') }}" alt="instagram" class="w-full h-full"/>
                    </div>
                    <p class="text-neutral-700 text-sm font-bold mt-2">اینستاگرام</p>
                    <p class="text-neutral-400 text-[8px] font-light">{{ $appointment->salon->instagram }}</p>
                </a>
            @endif
        </div>
        <div class="grid grid-cols-1 mt-3.5">
            <a href="tel:{{ $appointment->salon->support_phone_number }}"
               class="bg-white flex rounded-lg shadow p-4 text-center justify-center items-center">
                <div
                    class="w-10 h-10  rounded-full flex items-center justify-center">
                    <img src="{{ asset('assets/img/phone.png') }}" alt="instagram" class="w-full h-full"/>
                </div>
                <p class="text-neutral-700 text-sm font-bold ml-2 mt-2">{{ str_replace($englishDigits, $persianDigits, $appointment->salon->support_phone_number) }}</p>
            </a>
        </div>
    </section>

    <section class="mt-5">
        <div class="flex justify-end items-center">
            <h3 class="text-neutral-700 text-sm mr-2 font-bold text-right">موقعیت مکانی</h3>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="#353535" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="mt-4 bg-white rounded-lg shadow p-4">
            <p dir="rtl" class="text-neutral-700 bg-black/5 text-sm font-normal font-iranyekan leading-normal">{{ $appointment->salon->address }}</p>
        </div>

        <div class="mt-4 bg-white rounded-lg shadow p-4">
            <div id="map" class="mt-4 h-60 rounded-2xl"></div>
            <button id="navigate-btn" class="w-full mt-4 bg-teal-900 text-white py-2 rounded-[10px]">مسیریابی</button>
        </div>
    </section>

    <footer class="mt-8 flex justify-between items-center">
        <div class="text-zinc-400 text-sm font-medium border  rounded-lg py-2 px-4">
            نســخــه {{ str_replace($englishDigits, $persianDigits, '1.0.1') }}</div>
      <div class="flex flex-col justify-end">
          <div>
              <span class="text-teal-900 text-2xl font-black">بیوتی </span>
              <span class="text-orange-400 text-2xl font-black">سی</span>
              <span class="text-teal-900 text-2xl font-black"> آر ام</span>
          </div>
          <div class="text-zinc-400 text-[10px] font-normal ">اپلیکیشن مدیریت مشتریان</div>
      </div>
    </footer>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function toPersianDigits(str) {
            str = String(str);
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return str.replace(/[0-9]/g, function (w) {
                return persian[+w];
            });
        }

        const lat = parseFloat(document.querySelector('meta[name="lat"]').getAttribute('content'));
        const lang = parseFloat(document.querySelector('meta[name="lang"]').getAttribute('content'));

        if (lat && lang) {
            const map = L.map('map').setView([lat, lang], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            L.marker([lat, lang]).addTo(map);
            document.getElementById('navigate-btn').addEventListener('click', function () {
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lang}`, '_blank');
            });
        }

        const appointmentDateStr = document.querySelector('meta[name="appointment-date"]').getAttribute('content');
        const appointmentDateTime = new Date(appointmentDateStr).getTime();

        const countdownElement = document.getElementById("countdown");
        const x = setInterval(function () {
            const now = new Date().getTime();
            const distance = appointmentDateTime - now;

            if (distance < 0) {
                clearInterval(x);
                countdownElement.innerHTML = ''; // Or a message that the time has passed
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            countdownElement.innerHTML = `
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div class="bg-white  rounded-lg shadow p-2">
                            <div class="text-neutral-700 text-3xl font-bold font-iranyekan">${toPersianDigits(days)}</div>
                            <div class="text-neutral-400 text-sm font-light">روز</div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-2">
                            <div class="text-neutral-700 text-3xl font-bold font-iranyekan">${toPersianDigits(hours)}</div>
                            <div class="text-neutral-400 text-sm font-light">ساعت</div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-2">
                            <div class="text-neutral-700 text-3xl font-bold font-iranyekan">${toPersianDigits(minutes)}</div>
                            <div class="text-neutral-400 text-sm font-light">دقیقه</div>
                        </div>
                        <div class="bg-white  rounded-lg shadow p-2 flex flex-col justify-center items-center">
                         <div
                             class="w-10 h-10 mx-auto  rounded-full flex items-center justify-center">
                             <img src="{{ asset('assets/img/time.png') }}" alt="time" class="w-full h-full"/>
                         </div>
                            <p class="text-neutral-700 text-xs font-bold mt-1">زمان باقی مانده</p>
                        </div>
                    </div>
                `;
        }, 1000);
    });
</script>
</body>
</html>
