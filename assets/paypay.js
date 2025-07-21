document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("paypay-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const amount = document.getElementById("amount").value;
    const phone = document.getElementById("phone").value;
    const submitButton = document.getElementById("paypay-submit");
    const messageDiv = document.getElementById("paypay-message");

    // Validate inputs
    if (!amount || amount <= 0 || !phone.match(/^\d{9}$/)) {
      messageDiv.textContent = "Please enter a valid amount and phone number.";
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Processing...";
    messageDiv.textContent = "";

    fetch(PayPayData.ajax_url + "?action=create_paypay_payment", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        amount: amount,
        phone: phone,
        nonce: PayPayData.nonce,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          window.location.href = data.data.return_url;
        } else {
          messageDiv.textContent = data.data.message || "Payment failed.";
        }
      })
      .catch((error) => {
        messageDiv.textContent = "An error occurred. Please try again.";
      })
      .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = "Pay Now";
      });
  });
});
