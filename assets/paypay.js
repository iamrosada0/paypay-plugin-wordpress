document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("paypay-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const amount = document.getElementById("amount").value;
    const phone = document.getElementById("phone").value;
    const method = document.getElementById("payment_method").value;
    const messageDiv = document.getElementById("paypay-message");
    const modalDiv = document.getElementById("paypay-modal-content");
    const submitButton = document.getElementById("paypay-submit");

    // Validação
    if (
      !amount ||
      amount <= 0 ||
      (method === "MULTICAIXA_EXPRESS" && !phone.match(/^\d{9}$/))
    ) {
      messageDiv.textContent = "Por favor, insira os dados corretamente.";
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Processando...";
    messageDiv.textContent = "";
    modalDiv.innerHTML = "";

    const body = new URLSearchParams({
      amount: amount,
      nonce: PayPayData.nonce,
      action:
        method === "MULTICAIXA_EXPRESS"
          ? "create_paypay_payment"
          : "create_paypay_app_payment",
    });

    if (method === "MULTICAIXA_EXPRESS") {
      body.append("phone", phone);
    }

    fetch(PayPayData.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body,
    })
      .then((res) => res.json())
      .then((response) => {
        if (response.success && response.data) {
          const data = response.data;

          if (method === "PAYPAY_APP") {
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(
              data.dynamic_link
            )}`;
            modalDiv.innerHTML = `
              <p>Escaneie o QR code com o PayPay App para completar o pagamento:</p>
              <img src="${qrUrl}" alt="QR Code" class="w-40 h-40" style="margin: 1rem auto; display: block;" />
            `;
          } else if (method === "MULTICAIXA_EXPRESS") {
            modalDiv.innerHTML = `
              <p>Pagamento de <strong>${data.total_amount} AOA</strong> iniciado.</p>
              <p>Autorize no app MULTICAIXA Express com o número <strong>${phone}</strong> em até 90 segundos.</p>
            `;
          }
        } else {
          messageDiv.textContent =
            response.data?.message || "O pagamento falhou.";
        }
      })
      .catch(() => {
        messageDiv.textContent = "Erro na comunicação com o servidor.";
      })
      .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = "Pagar Agora";
      });
  });
});
