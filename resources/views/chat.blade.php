<form action="/chat" method="POST">
    @csrf
    <label for="message">Your Message:</label>
    <textarea name="message" id="message" rows="4"></textarea>
    <button type="submit">Send to ChatGPT</button>
</form>
