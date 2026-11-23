/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', '"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
        serif: ['"Playfair Display"', 'Georgia', 'serif'],
      },
      colors: {
        atelier: {
          50: '#FAF4F6',
          100: '#F5E9EC',
          200: '#EBD2D8',
          300: '#DCB0BC',
          400: '#BA7A8B',
          500: '#8E4D5E',  /* Malva Rosewood principal */
          600: '#793E4C',  /* Hover */
          700: '#64323E',
          800: '#502832',
          900: '#3D1D25',
          950: '#230F15',
        },
        espresso: {
          50: '#F7F6F6',
          100: '#EFECEE',
          200: '#DDD7DA',
          700: '#4A3B41',
          800: '#382B30',
          900: '#2D2327',  /* Charcoal espresso */
          950: '#1D1619',
        },
        nude: {
          50: '#FAF8F5',   /* Fondo porcelana principal */
          100: '#F4EFEA',  /* Superficie suave */
          200: '#EAE4DE',  /* Bordes suaves */
          300: '#DBD3CB',
        },
        /* Aliases para retrocompatibilidad limpia */
        studio: {
          50: '#FAF4F6',
          100: '#F5E9EC',
          200: '#EBD2D8',
          500: '#8E4D5E',
          600: '#793E4C',
          700: '#64323E',
          800: '#502832',
          900: '#2D2327',
          950: '#1D1619',
        },
        mint: {
          50: '#FAF8F5',
          100: '#F5E9EC',
          200: '#EBD2D8',
          500: '#8E4D5E',
          600: '#793E4C',
        }
      }
    },
  },
  plugins: [],
}


