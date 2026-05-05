import { TestBed } from '@angular/core/testing';

import { AppRoutesTs } from './app.routes.ts';

describe('AppRoutesTs', () => {
  let service: AppRoutesTs;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(AppRoutesTs);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
