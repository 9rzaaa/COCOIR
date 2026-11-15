<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'COCOIR — Eco-Friendly Coconut Coir Products' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,600;0,700;0,900;1,700;1,900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'coco-brown': '#202020',
                        'coco-dark': '#363636',
                        'coco-mid': '#727272',
                        'coco-tan': '#b7b7b0',
                        'coco-orange': '#252a24',
                        'coco-amber': '#d4d9cf',
                        'coco-green': '#55634e',
                        'coco-leaf': '#71806a',
                        'coco-sage': '#c5cec0',
                        'coco-cream': '#ffffff',
                        'coco-sand': '#f0f1ee',
                        'coco-white': '#ffffff',
                    },
                    fontFamily: {
                        'display': ['Barlow', 'sans-serif'],
                        'body': ['Lato', 'sans-serif'],
                    },
                    keyframes: {
                        'fade-up': {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(30px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        'fade-in': {
                            '0%': {
                                opacity: '0'
                            },
                            '100%': {
                                opacity: '1'
                            }
                        },
                        'float': {
                            '0%,100%': {
                                transform: 'translateY(0px)'
                            },
                            '50%': {
                                transform: 'translateY(-14px)'
                            }
                        },
                        'spin-slow': {
                            '0%': {
                                transform: 'rotate(0deg)'
                            },
                            '100%': {
                                transform: 'rotate(360deg)'
                            }
                        },
                        'sway': {
                            '0%,100%': {
                                transform: 'rotate(-3deg)'
                            },
                            '50%': {
                                transform: 'rotate(3deg)'
                            }
                        },
                    },
                    animation: {
                        'fade-up': 'fade-up 0.8s ease forwards',
                        'fade-in': 'fade-in 1s ease forwards',
                        'float': 'float 4s ease-in-out infinite',
                        'spin-slow': 'spin-slow 22s linear infinite',
                        'sway': 'sway 3s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
<link rel="stylesheet" href="<?= base_url('css/site.css') . '?v=' . filemtime(FCPATH . 'css/site.css') ?>">
</head>