let sessionToken = null;
let finalMsisdn = null;

const MODE = new URLSearchParams(window.location.search).get("mode") || "mock";

const messageBox = document.getElementById("message");
const msisdnSubmitBtn = document.getElementById("msisdnSubmitBtn");
const msisdnInput = document.getElementById("msisdn");

const pinInputs = document.querySelectorAll(".pin-box");
const pinSubmitBtn = document.getElementById("pinSubmitBtn");

const msisdnSection = document.getElementById("msisdnSection");
const pinSection = document.getElementById("pinSection");
const tqSection = document.getElementById("tqSection");

msisdnSubmitBtn.addEventListener("click", async (event) => {
    event.preventDefault();
    clearMessage();

    const localMsisdn = normalizeMsisdn(msisdnInput.value);

    if (!isValidLocal(localMsisdn)) {
        showMessage("error", "Please enter a valid mobile number.");
        return;
    }

    finalMsisdn = "965" + localMsisdn;

    try {
        setLoading(msisdnSubmitBtn, true, "CONTINUE");

        const checkSub = await backendPost("check_subscription", {
            msisdn: finalMsisdn
        });

        if (isAlreadySubscribed(checkSub)) {
            showMessage("error", checkSub?.message || "You are already subscribed.");
            return;
        }

        sessionToken = checkSub.session_token || checkSub.data?.session_token;

        if (!sessionToken) {
            console.error("Missing session token:", checkSub);
            showMessage("error", "Something went wrong. Please try again.");
            return;
        }

        showMessage("info", "Sending PIN...");

        const sendPin = await backendPost("send_pin", {
            msisdn: finalMsisdn,
            session_token: sessionToken
        });

        if (sendPin.code !== "PIN_SENT") {
            showMessage("error", sendPin.message || "Unable to send PIN.");
            return;
        }

        clearMessage();
        showPinStep();

    } catch (error) {
        catchError(error, "An error occurred.");
    } finally {
        setLoading(msisdnSubmitBtn, false, "CONTINUE");
    }
});

pinSubmitBtn.addEventListener("click", async (event) => {
    event.preventDefault();
    clearMessage();

    const pin = getPinCode();

    if (!isValidPin(pin)) {
        showMessage("error", `Please enter the ${pinInputs.length}-digit PIN.`);
        return;
    }

    if (!hasActiveSession()) {
        showMessage("error", "Session expired. Please start again.");
        resetFlow();
        return;
    }

    try {
        setLoading(pinSubmitBtn, true, "CONFIRM");

        showMessage("info", "Confirming PIN...");

        const checkPin = await backendPost("confirm_pin", {
            msisdn: finalMsisdn,
            pin,
            session_token: sessionToken
        });

        if (checkPin.code !== "PIN_CONFIRMED") {
            showMessage("error", checkPin.message || "Incorrect PIN. Please try again.");
            clearPinInputs();
            return;
        }

        showMessage("info", "Verifying subscription...");

        const finalCheck = await backendPost("check_subscription", {
            msisdn: finalMsisdn,
            session_token: sessionToken
        });

        if (!finalCheck.success) {
            showMessage("error", finalCheck.message || "Still processing. Please wait or try again.");
            return;
        }

        clearMessage();
        showTqStep();

    } catch (error) {
        catchError(error, "Confirmation error.");
        clearPinInputs();
    } finally {
        setLoading(pinSubmitBtn, false, "CONFIRM");
    }
});


// ================= API =================
async function backendPost(action, payload = {}) {
    let response;
    let data;

    try {
        response = await fetch("../api/index.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                action,
                payload: {
                    ...payload,
                    mode: MODE
                }
            })
        });
    } catch {
        throw new Error("Network error. Please try again.");
    }

    try {
        data = await response.json();
    } catch {
        throw new Error("Invalid server response.");
    }

    if (!response.ok || (data.success === false && data.code !== "ALREADY_SUBSCRIBED")) {
        throw new Error(data?.message || "Unable to process the request.");
    }

    return data;
}


// ================= FLOW HELPERS =================
function isAlreadySubscribed(response) {
    return response?.code === "ALREADY_SUBSCRIBED";
}

function hasActiveSession() {
    return Boolean(finalMsisdn && sessionToken);
}

function showPinStep() {
    showOnly(pinSection);
    setStep(1);
    pinInputs[0].focus();
}

function showTqStep() {
    showOnly(tqSection);
    setStep(2);
}

function resetFlow() {
    sessionToken = null;
    finalMsisdn  = null;

    clearPinInputs();
    showOnly(msisdnSection);
    msisdnInput.focus();
}

function showOnly(activeSection) {
    [msisdnSection, pinSection, tqSection].forEach(section => {
        section.classList.add("hidden");
    });

    activeSection.classList.remove("hidden");
}


// ================= VALIDATION =================
function normalizeMsisdn(input) {
    let msisdn = input.replace(/\D/g, "");

    msisdn = msisdn.replace(/^0+/, "");

    if (msisdn.startsWith("965")) {
        msisdn = msisdn.slice(3);
    }

    return msisdn;
}

function isValidLocal(msisdn) {
    return /^\d{8}$/.test(msisdn);
}

function isValidPin(pin) {
    return pin.length === pinInputs.length && /^\d+$/.test(pin);
}

function getPinCode() {
    return Array.from(pinInputs).map(input => input.value).join("");
}


// ================= UI HELPERS =================
function setLoading(button, isLoading, defaultText) {
    button.disabled    = isLoading;
    button.textContent = isLoading ? "PROCESSING..." : defaultText;
}

function showMessage(type, text) {
    messageBox.className   = `message ${type}`;
    messageBox.textContent = text;
}

function clearMessage() {
    messageBox.className   = "message hidden";
    messageBox.textContent = "";
}

function clearPinInputs() {
    pinInputs.forEach(input => input.value = "");
    pinInputs[0].focus();
}

function catchError(error, fallbackMessage) {
    console.error(error);
    showMessage("error", error.message || fallbackMessage);
}


// ================= PIN INPUT UX =================
pinInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/\D/g, "");

        if (e.target.value && index < pinInputs.length - 1) {
            pinInputs[index + 1].focus();
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !e.target.value && index > 0) {
            pinInputs[index - 1].focus();
        }
    });

    input.addEventListener("paste", (e) => {
        e.preventDefault();

        const pasted = (e.clipboardData || window.clipboardData)
            .getData("text")
            .replace(/\D/g, "");

        pasted.split("").forEach((char, i) => {
            if (pinInputs[i]) {
                pinInputs[i].value = char;
            }
        });

        const nextIndex = Math.min(pasted.length, pinInputs.length - 1);
        pinInputs[nextIndex].focus();
    });
});

function setStep(stepIndex) {
    const circles = document.querySelectorAll('.circle');

    circles.forEach(circle => {
        circle.classList.remove('blue');
        circle.classList.add('gray');
    });

    circles[stepIndex].classList.remove('gray');
    circles[stepIndex].classList.add('blue');
}