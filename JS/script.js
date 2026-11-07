const progress = document.getElementById("progress");
const prev = document.getElementById("prev");
const next = document.getElementById("next");
const circles = document.querySelectorAll(".circle");

const bookingStep1 = document.getElementById("step1");
const bookingStep2 = document.getElementById("step2");
const bookingStep3 = document.getElementById("step3");

if (progress && prev && next && circles.length && bookingStep1 && bookingStep2 && bookingStep3) {
  let currentActive = 1;

  next.addEventListener("click", () => {
    if (currentActive === circles.length) {
      return;
    }

    currentActive++;
    update();
    toggleContainers();
  });

  prev.addEventListener("click", () => {
    currentActive--;
    update();
    toggleContainers();
  });

  function update() {
    circles.forEach((circle, index) => {
      circle.classList.toggle("active", index < currentActive);
    });

    const progressWidth = ((currentActive - 1) / (circles.length - 1)) * 100;
    progress.style.width = `${progressWidth}%`;

    prev.disabled = currentActive === 1;
    next.innerText = currentActive === circles.length ? "Submit" : "Next";
  }

  function toggleContainers() {
    bookingStep1.style.display = currentActive === 1 ? "block" : "none";
    bookingStep2.style.display = currentActive === 2 ? "block" : "none";
    bookingStep3.style.display = currentActive === 3 ? "block" : "none";
  }

  update();
}
