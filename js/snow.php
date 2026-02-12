<div id="snow-layer"></div>

<script src="/js/particles.min.js"></script>

<script>
particlesJS("snow-layer", {
  particles: {
    number: {
      value: 120, // 雪的密集度
      density: {
        enable: true,
        value_area: 800
      }
    },
    color: {
      value: "#ffffff"
    },
    shape: {
      type: "circle"
    },
    opacity: {
      value: 0.7,
      random: true
    },
    size: {
      value: 4,
      random: true
    },

    line_linked: {
      enable: false
    },

    move: {
      enable: true,
      speed: 1.2,
      direction: "bottom",
      random: true,
      out_mode: "out"
    }
  },

  /* 👇 所有连线只在鼠标 hover 时出现 */
interactivity: {
  detect_on: "window",
  events: {
    onhover: {
      enable: true,
      mode: "grab"
    },
    onclick: {
      enable: false
    },
    resize: true
  },
  modes: {
    grab: {
      distance: 140,
      line_linked: {
        opacity: 0.6
      }
    }
  }
},

  retina_detect: true
});
</script>