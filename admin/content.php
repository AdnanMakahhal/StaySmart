<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['admin_role'] ?? 'admin') !== 'admin') {
    http_response_code(403);
    exit('Only an administrator can edit website content.');
}

require_once '../PHP/connect.php';
require_once '../PHP/site-content.php';

$pages = staySmartContentFields();
$requestedPage = $_GET['page'] ?? $_POST['page'] ?? 'home';
$pageKey = is_string($requestedPage) && isset($pages[$requestedPage]) ? $requestedPage : 'home';
$pageConfig = $pages[$pageKey];
$values = staySmartLoadContent($con);
$originalValues = $values;
$errorMessage = '';
$successMessage = $_SESSION['admin_content_message'] ?? '';
unset($_SESSION['admin_content_message']);

if (!isset($_SESSION['admin_content_token'])) {
    $_SESSION['admin_content_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['admin_content_token'], $submittedToken)) {
        $errorMessage = 'Your session expired. Reload the page and submit your changes again.';
    } else {
        $fieldDefinitions = [];
        foreach ($pageConfig['groups'] as $fields) {
            $fieldDefinitions += $fields;
        }

        foreach ($fieldDefinitions as $key => $field) {
            if ($field['type'] === 'image') {
                continue;
            }

            $postedValue = $_POST[$key] ?? null;
            if (!is_string($postedValue)) {
                $errorMessage = 'Some content fields were missing. Please review the form and try again.';
                break;
            }
            $postedValue = trim($postedValue);
            $maxLength = $field['type'] === 'textarea' ? 2000 : 255;
            if ($postedValue === '' || strlen($postedValue) > $maxLength) {
                $errorMessage = $field['label'] . ' is required and must be no more than ' . $maxLength . ' characters.';
                break;
            }
            if ($field['type'] === 'email' && !filter_var($postedValue, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'Enter a valid contact email address.';
                break;
            }
            $values[$key] = $postedValue;
        }

        $newImagePaths = [];
        if ($errorMessage === '') {
            $imageDirectory = __DIR__ . '/../Assets/site-content';
            $imageDirectoryReady = is_dir($imageDirectory) || mkdir($imageDirectory, 0755, true);
            if (!$imageDirectoryReady) {
                $errorMessage = 'Image storage is unavailable. Please try again later.';
            } else {
                foreach ($fieldDefinitions as $key => $field) {
                    if ($field['type'] !== 'image') {
                        continue;
                    }
                    $upload = $_FILES['image_' . $key] ?? null;
                    if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 8 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
                        $errorMessage = $field['label'] . ' must be a valid image under 8 MB.';
                        break;
                    }

                    $imageInfo = getimagesize($upload['tmp_name']);
                    $mimeToExtension = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    $mime = $imageInfo['mime'] ?? '';
                    if (!$imageInfo || !isset($mimeToExtension[$mime])) {
                        $errorMessage = $field['label'] . ' must be a JPEG, PNG, or WebP image.';
                        break;
                    }

                    $filename = bin2hex(random_bytes(16)) . '.' . $mimeToExtension[$mime];
                    $destination = $imageDirectory . '/' . $filename;
                    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                        $errorMessage = $field['label'] . ' could not be saved. Check image folder permissions.';
                        break;
                    }
                    $relativePath = 'Assets/site-content/' . $filename;
                    $values[$key] = $relativePath;
                    $newImagePaths[] = $destination;
                }
            }
        }

        if ($errorMessage === '') {
            try {
                $con->begin_transaction();
                $saveContent = $con->prepare(
                    'INSERT INTO site_content (content_key, content_value) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)'
                );
                foreach ($fieldDefinitions as $key => $field) {
                    $contentValue = $values[$key];
                    $saveContent->bind_param('ss', $key, $contentValue);
                    $saveContent->execute();
                }
                $saveContent->close();
                $con->commit();
                $_SESSION['admin_content_message'] = $pageConfig['label'] . ' content saved.';
                header('Location: content.php?page=' . rawurlencode($pageKey));
                exit;
            } catch (mysqli_sql_exception $exception) {
                $con->rollback();
                foreach ($newImagePaths as $newImagePath) {
                    if (is_file($newImagePath)) {
                        unlink($newImagePath);
                    }
                }
                foreach ($fieldDefinitions as $key => $field) {
                    if ($field['type'] === 'image') {
                        $values[$key] = $originalValues[$key];
                    }
                }
                error_log('StaySmart content save failed: ' . $exception->getMessage());
                $errorMessage = 'Your changes could not be saved. Please try again.';
            }
        } elseif ($newImagePaths) {
            foreach ($newImagePaths as $newImagePath) {
                if (is_file($newImagePath)) {
                    unlink($newImagePath);
                }
            }
            foreach ($fieldDefinitions as $key => $field) {
                if ($field['type'] === 'image') {
                    $values[$key] = $originalValues[$key];
                }
            }
        }
    }
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo $escape($pageConfig['label']); ?> content | StaySmart</title>
    <?php include '../PHP/head.php'; ?>
    <?php include 'adminlinks.php'; ?>
</head>

<body class="admin-content-page">
    <?php include '../PHP/header.php'; ?>
    <?php include 'admin-header.php'; ?>

    <main class="admin-content-main">
        <div class="admin-content-shell">
            <header class="admin-page-heading">
                <div>
                    <p class="admin-page-eyebrow">Website content</p>
                    <h1>Edit <?php echo $escape($pageConfig['label']); ?></h1>
                    <p>Update the text and images visitors see on this page.</p>
                </div>
                <a class="admin-content-preview-link" href="../<?php echo $escape($pageKey === 'home' ? 'index.php' : $pageKey . '.php'); ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Preview page
                </a>
            </header>

            <nav class="admin-content-tabs" aria-label="Choose page to edit">
                <?php foreach ($pages as $key => $pageInfo): ?>
                    <a href="content.php?page=<?php echo $escape($key); ?>"<?php echo $key === $pageKey ? ' class="is-active" aria-current="page"' : ''; ?>>
                        <?php echo $escape($pageInfo['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($successMessage !== ''): ?>
                <p class="admin-profile-notice is-success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?php echo $escape($successMessage); ?></p>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?>
                <p class="admin-profile-notice is-error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?php echo $escape($errorMessage); ?></p>
            <?php endif; ?>

            <form class="admin-content-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="page" value="<?php echo $escape($pageKey); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_content_token']); ?>">
                <?php foreach ($pageConfig['groups'] as $groupTitle => $fields): ?>
                    <section class="admin-content-group" aria-labelledby="group-<?php echo $escape($pageKey . '-' . array_key_first($fields)); ?>">
                        <div class="admin-content-group-heading">
                            <h2 id="group-<?php echo $escape($pageKey . '-' . array_key_first($fields)); ?>"><?php echo $escape($groupTitle); ?></h2>
                        </div>
                        <div class="admin-content-fields">
                            <?php foreach ($fields as $key => $field): ?>
                                <?php $fieldId = 'content-' . $key; ?>
                                <div class="admin-content-field<?php echo $field['type'] === 'image' || $field['type'] === 'textarea' ? ' admin-content-field-wide' : ''; ?>">
                                    <label for="<?php echo $escape($fieldId); ?>"><?php echo $escape($field['label']); ?></label>
                                    <?php if ($field['type'] === 'textarea'): ?>
                                        <textarea id="<?php echo $escape($fieldId); ?>" name="<?php echo $escape($key); ?>" maxlength="2000" rows="4" required><?php echo $escape($values[$key]); ?></textarea>
                                    <?php elseif ($field['type'] === 'image'): ?>
                                        <?php $imagePath = staySmartContentImage($values[$key], $field['default']); ?>
                                        <div class="admin-content-image-preview">
                                            <img src="../<?php echo $escape($imagePath); ?>" alt="Current <?php echo $escape($field['label']); ?>" loading="lazy">
                                            <span>Current image</span>
                                        </div>
                                        <input id="<?php echo $escape($fieldId); ?>" type="file" name="image_<?php echo $escape($key); ?>" accept="image/jpeg,image/png,image/webp">
                                        <small>Choose a new JPEG, PNG, or WebP image under 8 MB to replace it.</small>
                                    <?php else: ?>
                                        <input id="<?php echo $escape($fieldId); ?>" type="<?php echo $escape($field['type']); ?>" name="<?php echo $escape($key); ?>" value="<?php echo $escape($values[$key]); ?>" maxlength="255" required>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
                <div class="admin-content-actions">
                    <button class="admin-content-save" type="submit">
                        Save changes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include '../PHP/footer.php'; ?>
</body>

</html>
