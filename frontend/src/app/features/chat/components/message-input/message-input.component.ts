import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Attachment } from '../../../../shared/models';

@Component({
  selector: 'app-message-input',
  standalone: true,
  imports: [CommonModule, FormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './message-input.component.html',
})
export class MessageInputComponent {
  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;

  @Input() messageText = '';
  @Input() pendingAttachments: Attachment[] = [];
  @Input() isUploading = false;
  @Input() isSending = false;

  @Output() messageTextChange = new EventEmitter<string>();
  @Output() sendMessage = new EventEmitter<void>();
  @Output() fileSelected = new EventEmitter<File>();
  @Output() removeAttachment = new EventEmitter<number>();

  get canSend(): boolean {
    return this.messageText.trim().length > 0 || this.pendingAttachments.length > 0;
  }

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) {
      this.fileSelected.emit(file);
      input.value = ''; // Reset for same file selection
    }
  }

  triggerFileInput(): void {
    this.fileInput.nativeElement.click();
  }

  onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      if (this.canSend) {
        this.sendMessage.emit();
      }
    }
  }

  isImageAttachment(att: Attachment): boolean {
    return att.mime_type?.startsWith('image/') ?? false;
  }
}
