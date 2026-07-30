@props([
    'email' => 'support@parcelmanexpress.com',
    'phone' => '+233 55 406 4189',
    'address' => 'Circle Same Building as IPMC, Accra'
])

<section class="pm-section pm-contact-section" id="contact">
    <div class="pm-contact-wrapper">
        <div class="pm-contact-card">
            
            <!-- Left Side Column (Image on Top, Details Below on Plain BG) -->
            <div class="pm-contact-left-col">
                <div class="pm-contact-img">
                    <img src="/contact.jpg" alt="Talk to Parcelman">
                </div>
                
                <div class="pm-contact-info-list">
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><img src="/mail.png" alt="Email"></div>
                        <div>
                            <small>Email</small>
                            <strong>{{ $email }}</strong>
                        </div>
                    </div>
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><img src="/phone.png" alt="Phone"></div>
                        <div>
                            <small>Phone</small>
                            <strong>{{ $phone }}</strong>
                        </div>
                    </div>
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><img src="/location_on.png" alt="Location"></div>
                        <div>
                            <small>Accra</small>
                            <strong>{{ $address }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side Form -->
            <div class="pm-contact-form-side">
                <h2>Talk to Parcelman about your delivery needs.</h2>
                <p>Our friendly team would love to hear from you.</p>

                <form action="#" method="POST">
                    @csrf
                    <div class="pm-form-group">
                        <label>First name</label>
                        <input type="text" placeholder="John Doe" required>
                    </div>

                    <div class="pm-form-group">
                        <label>Email (Optional)</label>
                        <input type="email" placeholder="joedoe@example.com">
                    </div>

                    <div class="pm-form-group">
                        <label>Phone number</label>
                        <input type="text" value="{{ $phone }}" required>
                    </div>

                    <div class="pm-form-group">
                        <label>Message</label>
                        <textarea rows="3" placeholder="Leave us a message"></textarea>
                    </div>

                    <button type="submit" class="pm-submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>