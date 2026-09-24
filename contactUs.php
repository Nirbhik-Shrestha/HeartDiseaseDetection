<?php
include("connection.php");
session_start();

$errors = [];
$values = ['rname' => '', 'remail' => '', 'rsubject' => '', 'rmessage' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $unused) {
        $values[$key] = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    }

    if ($values['rname'] === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($values['remail'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address so we can reply.';
    }
    if ($values['rsubject'] === '') {
        $errors[] = 'Please add a subject.';
    }
    if ($values['rmessage'] === '') {
        $errors[] = 'Please write your message.';
    } elseif (mb_strlen($values['rmessage']) > 5000) {
        $errors[] = 'Your message can be at most 5000 characters.';
    }

    if (!$errors) {
        $name    = mb_substr($values['rname'], 0, 100);
        $email   = mb_substr($values['remail'], 0, 100);
        $subject = mb_substr($values['rsubject'], 0, 150);
        $stmt = $con->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $values['rmessage']);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            // Redirect so refreshing the page does not send the message twice.
            $_SESSION['contact_sent'] = true;
            $con->close();
            header("Location: contactUs.php");
            exit();
        }
        $errors[] = 'Your message could not be sent right now. Please try again later, or email us directly.';
    }
}

$sent = !empty($_SESSION['contact_sent']);
unset($_SESSION['contact_sent']);
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - DaaktarSahab</title>
    <link rel="stylesheet" href="css/site.css">
    <style>
        .contact-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .contact-list li {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 14px 0;
            border-bottom: 1px solid #eef3f6;
        }

        .contact-list li:last-child {
            border-bottom: none;
        }

        .contact-list img {
            flex: none;
            width: 40px;
            height: 40px;
            padding: 9px;
            border-radius: 50%;
            background: #e6f6f4;
        }

        .contact-list span {
            display: block;
            color: #6b8193;
            font-size: 14px;
        }

        .contact-list a,
        .contact-list strong {
            color: #12304a;
            font-size: 16.5px;
            font-weight: 600;
            text-decoration: none;
        }

        .site-page .field textarea {
            width: 100%;
            min-height: 160px;
            padding: 12px 14px;
            border: 1px solid #cfdbe3;
            border-radius: 10px;
            background: #fff;
            color: #12304a;
            font-size: 16px;
            line-height: 1.5;
            resize: vertical;
        }

        .site-page .field textarea:focus {
            outline: none;
            border-color: #00a99d;
            box-shadow: 0 0 0 4px rgba(0, 169, 157, 0.18);
        }
    </style>
</head>
<body class="site-page">

<?php include('mainHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Contact</span>
        <h1>Get in touch</h1>
        <p>Questions, concerns or feedback? Send us a message and we'll get back to you by email.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($sent): ?>
        <div class="notice notice-ok">Thanks, your message was sent. We'll reply to the email address you gave.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice notice-bad" role="alert">
            <strong>Your message was not sent:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title">Send us a message</h2>
            </div>

            <form action="contactUs.php" method="POST">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Your name</label>
                        <input type="text" id="name" name="rname" value="<?= htmlspecialchars($values['rname']) ?>" maxlength="100" required autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="remail" value="<?= htmlspecialchars($values['remail']) ?>" maxlength="100" required autocomplete="email">
                    </div>
                    <div class="field field--wide">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="rsubject" value="<?= htmlspecialchars($values['rsubject']) ?>" maxlength="150" required>
                    </div>
                    <div class="field field--wide">
                        <label for="message">Message</label>
                        <textarea id="message" name="rmessage" maxlength="5000" required><?= htmlspecialchars($values['rmessage']) ?></textarea>
                        <p class="hint">Please don't include medical test results here. For advice about your health, book a doctor.</p>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">Send message</button>
            </form>
        </section>

        <aside class="panel">
            <p class="panel__label">Contact details</p>
            <ul class="contact-list">
                <li>
                    <img src="images/mail.png" alt="">
                    <div><span>Email</span><a href="mailto:support@daaktarsahab.com">support@daaktarsahab.com</a></div>
                </li>
                <li>
                    <img src="images/contact.png" alt="">
                    <div><span>Phone</span><a href="tel:+97712345678">+977-1-2345678</a></div>
                </li>
                <li>
                    <img src="images/location.png" alt="">
                    <div><span>Address</span><strong>123 Healthcare Street, Kathmandu, Nepal</strong></div>
                </li>
            </ul>
        </aside>
    </div>
</main>

<?php include('footer.html'); ?>
</body>
</html>
