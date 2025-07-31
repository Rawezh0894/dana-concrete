# Notification Sound Setup

## Required Files

To enable notification sounds, you need to add an MP3 file named `notification.mp3` in this directory.

## File Requirements

- **Format**: MP3
- **Duration**: 1-3 seconds (short notification sound)
- **Size**: Less than 100KB recommended
- **Quality**: 128kbps or higher

## How to Add

1. Download or create a short notification sound
2. Rename it to `notification.mp3`
3. Place it in this directory: `assets/sounds/notification.mp3`

## Testing

After adding the file:
1. Go to the concrete receipts page
2. Click the "تاقیکردنەوەی زەنگ" button
3. Check the browser console for debugging information
4. The sound should play when a new note is added

## Troubleshooting

If the sound doesn't play:
1. Check browser console for error messages
2. Ensure the file exists and is accessible
3. Check browser audio permissions
4. Try clicking the test button first (user interaction required) 