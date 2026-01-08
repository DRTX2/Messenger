import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy, ViewChild, ElementRef, AfterViewChecked, OnChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UIMessage, Attachment } from '../../../../shared/models';

@Component({
  selector: 'app-message-list',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './message-list.component.html',
})
export class MessageListComponent implements AfterViewChecked, OnChanges {
  @Input() messages: UIMessage[] = [];
  @Input() otherUserAvatar = '';

  @Output() toggleFavorite = new EventEmitter<UIMessage>();
  @Output() deleteMessage = new EventEmitter<number>();

  @ViewChild('scrollContainer') private scrollContainer!: ElementRef;

  private shouldScrollToBottom = false;

  ngOnChanges(): void {
    this.shouldScrollToBottom = true;
  }

  ngAfterViewChecked(): void {
    if (this.shouldScrollToBottom) {
      this.scrollToBottom();
      this.shouldScrollToBottom = false;
    }
  }

  private scrollToBottom(): void {
    try {
      const element = this.scrollContainer.nativeElement;
      element.scrollTo({
        top: element.scrollHeight,
        behavior: 'smooth'
      });
    } catch (err) {}
  }

  isImageAttachment(att: Attachment): boolean {
    return att.mime_type?.startsWith('image/') ?? false;
  }
}
