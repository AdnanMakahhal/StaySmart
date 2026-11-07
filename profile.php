<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
include './PHP/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userQuery = $con->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$userQuery->close();
$demoProfile=null;
if($user&&str_ends_with($user['email'],'@staysmart.example')&&$con->query("SHOW TABLES LIKE 'demo_guest_profiles'")->num_rows){$demoQuery=$con->prepare('SELECT city,country,statement FROM demo_guest_profiles WHERE user_id=?');$demoQuery->bind_param('i',$userId);$demoQuery->execute();$demoProfile=$demoQuery->get_result()->fetch_assoc();}

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_POST['update'])) {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $upload = $_FILES['profile_photo'] ?? null;
    $uploadPath = null;
    $uploadDirectory = __DIR__ . '/Assets/profile-photos';
    $avatarFile = $uploadDirectory . '/user-' . $userId . '.png';

    if ($username === '' || $fullName === '' || $email === '') {
        $_SESSION['profile_error'] = "Please complete all profile fields.";
    } else {
        if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['profile_error'] = "The photo could not be uploaded. Choose a smaller image and try again.";
            } elseif ($upload['size'] > 8 * 1024 * 1024) {
                $_SESSION['profile_error'] = "The selected photo is too large. Choose an image under 8 MB.";
            } else {
                $imageInfo = getimagesize($upload['tmp_name']);
                if (
                    !$imageInfo
                    || $imageInfo['mime'] !== 'image/png'
                    || $imageInfo[0] !== 512
                    || $imageInfo[1] !== 512
                ) {
                    $_SESSION['profile_error'] = "We couldn't process that photo. Please choose it again.";
                } elseif (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $_SESSION['profile_error'] = "Profile photo storage is unavailable. Please try again later.";
                } else {
                    $uploadPath = $uploadDirectory . '/user-' . $userId . '-' . bin2hex(random_bytes(8)) . '.tmp';
                    if (!move_uploaded_file($upload['tmp_name'], $uploadPath)) {
                        $uploadPath = null;
                        $_SESSION['profile_error'] = "Your photo couldn't be saved. Please try again.";
                    }
                }
            }
        }

        if (!isset($_SESSION['profile_error'])) {
            $update = $con->prepare("UPDATE users SET username = ?, full_name = ?, email = ? WHERE id = ?");
            $update->bind_param("sssi", $username, $fullName, $email, $userId);

            if ($update->execute()) {
                $_SESSION['user_name'] = $username;
                $_SESSION['profile_message'] = "Your profile has been updated.";

                if ($uploadPath) {
                    $backupPath = $uploadDirectory . '/user-' . $userId . '-' . bin2hex(random_bytes(8)) . '.bak';
                    $hasExistingAvatar = is_file($avatarFile);
                    $avatarBackedUp = !$hasExistingAvatar || rename($avatarFile, $backupPath);

                    if ($avatarBackedUp && rename($uploadPath, $avatarFile)) {
                        if ($hasExistingAvatar) {
                            unlink($backupPath);
                        }
                        $_SESSION['profile_message'] = "Your profile and photo have been updated.";
                    } else {
                        if ($avatarBackedUp && $hasExistingAvatar) {
                            rename($backupPath, $avatarFile);
                        }
                        if (is_file($uploadPath)) {
                            unlink($uploadPath);
                        }
                        $_SESSION['profile_error'] = "Your profile details were saved, but the new photo could not be applied.";
                    }
                }
            } else {
                if ($uploadPath && is_file($uploadPath)) {
                    unlink($uploadPath);
                }
                $_SESSION['profile_error'] = "We couldn't update your profile. Please try again.";
                unset($_SESSION['profile_message']);
            }

            $update->close();
        } elseif ($uploadPath && is_file($uploadPath)) {
            unlink($uploadPath);
        }
    }

    header("Location: profile.php");
    exit;
}

$displayName = $user['full_name'] !== '' ? $user['full_name'] : $user['username'];
$avatarFile = __DIR__ . '/Assets/profile-photos/user-' . $userId . '.png';
$avatarUrl = is_file($avatarFile)
    ? './Assets/profile-photos/user-' . $userId . '.png?v=' . filemtime($avatarFile)
    : './Assets/guest-avatar-default.png';
$profileMessage = $_SESSION['profile_message'] ?? null;
$profileError = $_SESSION['profile_error'] ?? null;
unset($_SESSION['profile_message'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './PHP/head.php'; ?>
    <link rel="stylesheet" href="./CSS/profile.css">
    <link rel="stylesheet" href="./CSS/reviews.css">
</head>

<body class="profile-page">
    <?php include './PHP/header.php'; ?>

    <main class="profile-main<?php echo ($profileMessage || $profileError) ? ' has-profile-notice' : ''; ?>">
        <?php if ($profileError): ?>
            <div class="profile-notice profile-notice-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="profile-notice-dismiss" type="button" aria-label="Dismiss message"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        <?php elseif ($profileMessage): ?>
            <div class="profile-notice profile-notice-success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($profileMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                <button class="profile-notice-dismiss" type="button" aria-label="Dismiss message"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        <?php endif; ?>
        <section class="profile-shell" aria-labelledby="profile-title">
            <header class="profile-heading">
                <p class="profile-eyebrow">Your account</p>
                <h1 id="profile-title">Profile settings</h1>
                <p>Keep your account details up to date and find your bookings in one place.</p>
            </header>

            <div class="profile-layout">
                <section class="profile-card" aria-label="Edit your profile">
                    <div class="profile-card-content">
                        <form class="profile-form" action="profile.php" method="post" enctype="multipart/form-data">
                            <div class="profile-user">
                                <input class="profile-photo-input" type="file" id="profile-photo" name="profile_photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a profile photo">
                                <label class="profile-avatar-control" for="profile-photo">
                                    <img class="profile-avatar" src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="88" height="88">
                                    <span class="profile-avatar-camera"><i class="fa-solid fa-camera" aria-hidden="true"></i></span>
                                </label>
                                <div class="profile-user-copy">
                                    <h2><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <p class="profile-photo-hint">Click your photo to choose an image, then adjust it to fit the frame.</p>
                                    <p class="profile-photo-status" role="status" aria-live="polite"></p>
                                </div>
                            </div>
                            <div class="profile-field">
                                <label for="full-name">Full name</label>
                                <input type="text" id="full-name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" maxlength="150" required>
                            </div>
                            <div class="profile-field">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
                            </div>
                            <div class="profile-field">
                                <label for="email">Email address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required>
                            </div>
                            <button class="profile-save" type="submit" name="update">
                                Save changes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                        <dialog class="profile-crop-dialog" aria-labelledby="crop-dialog-title">
                            <div class="profile-crop-dialog-content">
                                <header class="profile-crop-dialog-header">
                                    <div>
                                        <h2 id="crop-dialog-title">Adjust photo</h2>
                                        <p>Drag to position. Use the slider to zoom.</p>
                                    </div>
                                    <button class="profile-crop-close" type="button" aria-label="Cancel photo selection">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                </header>
                                <div class="profile-crop-preview-wrap">
                                    <canvas class="profile-crop-preview" width="512" height="512" aria-label="Profile photo crop preview" tabindex="0"></canvas>
                                </div>
                                <div class="profile-crop-controls">
                                    <div class="profile-zoom-control">
                                        <button class="profile-zoom-button profile-zoom-out" type="button" aria-label="Zoom out">
                                            <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                        </button>
                                        <label class="visually-hidden" for="profile-photo-zoom">Zoom photo</label>
                                        <input type="range" id="profile-photo-zoom" min="1" max="3" step="0.01" value="1">
                                        <button class="profile-zoom-button profile-zoom-in" type="button" aria-label="Zoom in">
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="profile-crop-error" role="status" aria-live="polite"></p>
                                <footer class="profile-crop-actions">
                                    <button class="profile-crop-cancel" type="button">Cancel</button>
                                    <button class="profile-crop-use" type="button" disabled>Use photo</button>
                                </footer>
                            </div>
                        </dialog>
                    </div>
                </section>

                <aside class="profile-actions" aria-label="Account links">
                    <div class="profile-action-card">
                        <span class="profile-action-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
                        <?php if($demoProfile): ?><span class="account-pill">Fictional demo account</span><p><?php echo htmlspecialchars($demoProfile['city'].', '.$demoProfile['country'],ENT_QUOTES,'UTF-8'); ?></p><p><?php echo htmlspecialchars($demoProfile['statement'],ENT_QUOTES,'UTF-8'); ?></p><?php endif; ?>
                        <h2>Your bookings</h2>
                        <p>Review upcoming stays and manage your reservations.</p>
                        <a href="./mybooking.php">View my bookings <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        <a href="./account-history.php">View my account history <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        <a href="#my-reviews">Your ratings &amp; reviews <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="profile-action-card profile-logout-card">
                        <span class="profile-action-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                        <h2>Leaving already?</h2>
                        <p>Sign out safely from your StaySmart account.</p>
                        <a href="./PHP/logout.php">Log out <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </aside>
            </div>
        </section>
        <?php $reviewUserId=$userId; include __DIR__.'/PHP/my-reviews.php'; ?>
    </main>

    <?php include './PHP/footer.php'; ?>
    <script>
        const noticeDismiss = document.querySelector(".profile-notice-dismiss");
        if (noticeDismiss) {
            noticeDismiss.addEventListener("click", (event) => {
                event.currentTarget.closest(".profile-notice").remove();
            });
        }

        const profileForm = document.querySelector(".profile-form");
        const photoInput = document.getElementById("profile-photo");
        const photoPreview = document.querySelector(".profile-avatar");
        const photoStatus = document.querySelector(".profile-photo-status");
        const saveButton = document.querySelector(".profile-save");
        const cropDialog = document.querySelector(".profile-crop-dialog");
        const cropCanvas = document.querySelector(".profile-crop-preview");
        const cropContext = cropCanvas.getContext("2d");
        const zoomInput = document.getElementById("profile-photo-zoom");
        const zoomOutButton = document.querySelector(".profile-zoom-out");
        const zoomInButton = document.querySelector(".profile-zoom-in");
        const usePhotoButton = document.querySelector(".profile-crop-use");
        const cropError = document.querySelector(".profile-crop-error");
        const savedAvatar = photoPreview.src;
        let selectedImage = null;
        let croppedBlob = null;
        let photoProcessing = false;
        let photoReadyToSubmit = false;
        let photoConfirmed = false;
        let previewUrl = null;
        let sourceUrl = null;
        let zoom = 1;
        let offsetX = 0;
        let offsetY = 0;
        let dragStart = null;
        let previewUpdateTimer = null;
        let previewUpdateId = 0;

        const getCropPosition = () => {
            const baseScale = Math.max(
                cropCanvas.width / selectedImage.naturalWidth,
                cropCanvas.height / selectedImage.naturalHeight
            );
            const width = selectedImage.naturalWidth * baseScale * zoom;
            const height = selectedImage.naturalHeight * baseScale * zoom;
            const x = Math.min(0, Math.max(cropCanvas.width - width, (cropCanvas.width - width) / 2 + offsetX));
            const y = Math.min(0, Math.max(cropCanvas.height - height, (cropCanvas.height - height) / 2 + offsetY));
            offsetX = x - (cropCanvas.width - width) / 2;
            offsetY = y - (cropCanvas.height - height) / 2;
            return { x, y, width, height };
        };

        const drawCrop = () => {
            if (!selectedImage) return;
            const crop = getCropPosition();
            cropContext.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
            cropContext.drawImage(selectedImage, crop.x, crop.y, crop.width, crop.height);
            photoProcessing = true;
            saveButton.disabled = true;

            clearTimeout(previewUpdateTimer);
            const updateId = ++previewUpdateId;
            previewUpdateTimer = setTimeout(() => {
                cropCanvas.toBlob((blob) => {
                    if (!blob || updateId !== previewUpdateId) return;
                    croppedBlob = blob;
                    photoProcessing = false;
                    usePhotoButton.disabled = false;
                }, "image/jpeg", 0.86);
            }, 120);
        };

        const cancelPhotoSelection = () => {
            if (cropDialog.open) cropDialog.close();
            photoInput.value = "";
            selectedImage = null;
            croppedBlob = null;
            photoConfirmed = false;
            photoProcessing = false;
            saveButton.disabled = false;
            usePhotoButton.disabled = true;
            photoPreview.src = savedAvatar;
            photoStatus.textContent = "";
            cropError.textContent = "";
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            if (sourceUrl) {
                URL.revokeObjectURL(sourceUrl);
                sourceUrl = null;
            }
        };

        photoInput.addEventListener("change", () => {
            const file = photoInput.files[0];
            if (!file) return;

            selectedImage = null;
            croppedBlob = null;
            photoReadyToSubmit = false;
            photoConfirmed = false;
            photoPreview.src = savedAvatar;
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                photoInput.value = "";
                photoPreview.src = savedAvatar;
                photoStatus.textContent = "Choose a JPEG, PNG or WebP image.";
                return;
            }
            if (file.size > 8 * 1024 * 1024) {
                photoInput.value = "";
                photoPreview.src = savedAvatar;
                photoStatus.textContent = "Choose an image under 8 MB.";
                return;
            }

            photoProcessing = true;
            usePhotoButton.disabled = true;
            cropError.textContent = "";
            if (sourceUrl) URL.revokeObjectURL(sourceUrl);
            sourceUrl = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
                selectedImage = image;
                zoom = 1;
                offsetX = 0;
                offsetY = 0;
                zoomInput.value = "1";
                cropDialog.showModal();
                drawCrop();
            };
            image.onerror = () => {
                photoProcessing = false;
                photoInput.value = "";
                photoPreview.src = savedAvatar;
                photoStatus.textContent = "We couldn't open that image. Please choose another.";
            };
            image.src = sourceUrl;
        });

        zoomOutButton.addEventListener("click", () => {
            zoomInput.value = String(Math.max(Number(zoomInput.min), zoom - 0.15));
            zoomInput.dispatchEvent(new Event("input", { bubbles: true }));
        });

        zoomInButton.addEventListener("click", () => {
            zoomInput.value = String(Math.min(Number(zoomInput.max), zoom + 0.15));
            zoomInput.dispatchEvent(new Event("input", { bubbles: true }));
        });

        zoomInput.addEventListener("input", () => {
            zoom = Number(zoomInput.value);
            usePhotoButton.disabled = true;
            drawCrop();
        });

        cropCanvas.addEventListener("pointerdown", (event) => {
            if (!selectedImage) return;
            const bounds = cropCanvas.getBoundingClientRect();
            dragStart = {
                pointerId: event.pointerId,
                x: event.clientX,
                y: event.clientY,
                offsetX,
                offsetY,
                scale: cropCanvas.width / bounds.width
            };
            cropCanvas.setPointerCapture(event.pointerId);
        });

        cropCanvas.addEventListener("pointermove", (event) => {
            if (!dragStart || event.pointerId !== dragStart.pointerId) return;
            offsetX = dragStart.offsetX + (event.clientX - dragStart.x) * dragStart.scale;
            offsetY = dragStart.offsetY + (event.clientY - dragStart.y) * dragStart.scale;
            drawCrop();
        });

        const endCropDrag = () => {
            dragStart = null;
        };
        cropCanvas.addEventListener("pointerup", endCropDrag);
        cropCanvas.addEventListener("pointercancel", endCropDrag);

        document.querySelector(".profile-crop-cancel").addEventListener("click", cancelPhotoSelection);
        document.querySelector(".profile-crop-close").addEventListener("click", cancelPhotoSelection);
        cropDialog.addEventListener("cancel", (event) => {
            event.preventDefault();
            cancelPhotoSelection();
        });
        cropDialog.addEventListener("click", (event) => {
            if (event.target === cropDialog) cancelPhotoSelection();
        });
        usePhotoButton.addEventListener("click", () => {
            if (!selectedImage || !croppedBlob || photoProcessing || usePhotoButton.disabled) return;
            photoConfirmed = true;
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = URL.createObjectURL(croppedBlob);
            photoPreview.src = previewUrl;
            cropDialog.close();
            photoStatus.textContent = "Photo selected. Save changes to apply it.";
            saveButton.disabled = false;
        });

        profileForm.addEventListener("submit", (event) => {
            if (photoReadyToSubmit || !photoInput.files.length || !photoConfirmed) return;
            event.preventDefault();

            if (photoProcessing || !selectedImage) {
                photoStatus.textContent = "Wait for the photo preview to finish before saving.";
                return;
            }

            saveButton.disabled = true;
            saveButton.innerHTML = 'Saving changes... <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';

            cropCanvas.toBlob((blob) => {
                if (!blob) {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save changes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
                    photoStatus.textContent = "We couldn't prepare the photo. Please try again.";
                    return;
                }

                const processedFile = new File([blob], "profile-photo.png", { type: "image/png" });
                const transfer = new DataTransfer();
                transfer.items.add(processedFile);
                photoInput.files = transfer.files;
                photoReadyToSubmit = true;
                saveButton.disabled = false;
                profileForm.requestSubmit(saveButton);
            }, "image/png");
        });
    </script>
</body>

</html>
