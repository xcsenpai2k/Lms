<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.98.0">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="{{ asset('/admin/css/signin.css') }}" rel="stylesheet">
    <style>
        .user-accounts {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .account {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px;
            padding: 20px;
            width: 250px;
        }

        .account h2 {
            color: #333;
            font-size: 18px;
            margin-top: 0;
        }

        .account p {
            margin: 5px 0;
        }
    </style>
</head>

<body class="text-center">
    
    <main class="form-signin w-100 m-auto">
        <form action="{{ route('login.post') }}" method="post">
            @csrf
            <img class="mb-4" src="https://co-well.vn/wp-content/themes/cowell/assets/img/logo-1.png" alt=""
                width="auto" height="60">
            <h1 class="h3 mb-3 fw-normal">Đăng nhập</h1>

            <div class="form-floating">
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    id="floatingInput" placeholder="name@example.com">
                <label for="floatingInput">E-mail</label>
                @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>
            <div class="form-floating">
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                    id="floatingPassword" placeholder="Password">
                <label for="floatingPassword">Mật khẩu</label>
                @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" name="rememberme"> Lưu đăng nhập
                </label>
            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Đăng nhập</button>
            <a href="{{ route('forgotPassword.form') }}">Quên mật khẩu</a> -
            <a href="{{ route('register.form') }}">Đăng kí thành viên mới</a>
            <p class="mt-5 mb-3 text-muted">&copy; 2017–2022</p>
        </form>
        <div class="user-accounts">
            <div class="account">
                <h2>Account</h2>
                <p>Admin: admin@example.com</p>
                <p>Teacher:teacher@example.com</p>
                <p>Student:student@example.com</p>
                <p>Password: 1234567@</p>
            </div>
        </div>
    </main>
</body>
</html>