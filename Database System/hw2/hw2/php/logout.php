<?php

    session_start();

    session_destroy();

    echo "<script>alert('Sign out success');</script>";
    echo "<script>window.location.href = 'https://localhost/hw2/'</script>";

?>