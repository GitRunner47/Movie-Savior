function flash(message = "", color = "info") {
    const flash = document.getElementById("flash");
    const outerDiv = document.createElement("div");
    outerDiv.className = "row justify-content-center";

    const innerDiv = document.createElement("div");
    innerDiv.className = `alert alert-${color}`;
    // Temporary prefix for clearer evidence gathering; remove after all milestones are complete.
    innerDiv.innerText = `(js) ${message}`;

    outerDiv.appendChild(innerDiv);
    flash.appendChild(outerDiv);
}

function validate_email(emailInput, errors) {
    if (emailInput.validity.valueMissing) {
        errors.push("Enter an email address.");
        return false;
    } else if (!emailInput.validity.valid) {
        errors.push("Enter a valid email address.");
        return false;
    }

    return true;
}

function validate_username(usernameInput, errors) {
    if (
        usernameInput.validity.valueMissing
        || usernameInput.validity.tooShort
        || usernameInput.validity.tooLong
        || usernameInput.validity.patternMismatch
    ) {
        errors.push("Use 3-30 lowercase letters, numbers, underscores, or hyphens.");
        return false;
    }

    return true;
}

function validate_password(passwordInput, errors) {
    if (passwordInput.validity.valueMissing) {
        errors.push("Enter a password.");
        return false;
    } else if (passwordInput.validity.tooShort) {
        errors.push("Password must be at least 8 characters.");
        return false;
    }

    return true;
}

function validate_passwords_match(passwordInput, confirmInput, errors) {
    if (confirmInput.validity.valueMissing) {
        errors.push("Confirm your password.");
        return false;
    } else if (passwordInput.value !== confirmInput.value) {
        errors.push("Passwords must match.");
        return false;
    }

    return true;
}

function show_validation_errors(errors) {
    document.getElementById("flash").innerHTML = "";
    errors.forEach((error) => flash(error, "danger"));
    return errors.length === 0;
}