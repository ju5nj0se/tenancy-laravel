<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrate tenants</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-wrapper">
                <a href="#" class="brand-logo">Administrate tenants</a>
            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            <h3>Form for create new tenants</h3>
            @if (session('success'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('success') }}
                </div>
            @endif
            <form action=" {{ route('tenant.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="input-field col s12">
                        <div class="input-field">
                            <input type="text" id="name" name="name" class="autocomplete" required>
                            <label for="name">Enter the name of the subdomain for you tenant new tenant</label>
                        </div>
                    </div>
                    
                    <button class="btn blue waves-effect waves-light mx-10" type="submit" name="action" style="display: inline-flex; align-items: center; justify-content: center;">Submit
                        <img src="{{ asset('send.svg') }}" alt="Send" style="margin-left: 10px;">
                    </button>
                    <div class="progress" id="loader" style="display: none;">
                        <div class="indeterminate"></div>
                    </div>
                </div>
            </form>
        </div>
    </main>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.querySelector('form');
            var loader = document.getElementById('loader');
            
            form.addEventListener('submit', function() {
                loader.style.display = 'block';
            });
        });
    </script>
</body>
</html>